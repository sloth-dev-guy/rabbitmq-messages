<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageAlreadyRegisterException;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageRetriesExhaustedException;
use SlothDevGuy\RabbitMQMessages\Interfaces\SkipListenMessageThrowable;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\Pipes\Casts\CastAMQPMessageProperties;
use SlothDevGuy\RabbitMQMessages\RabbitMQJob;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use Throwable;

class MessageListener
{
    public function __construct(
        protected ListenMessageModel $listenMessageModel,
    )
    {

    }

    /**
     * @param RabbitMQJob $job
     * @param Closure $next
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function handle(RabbitMQJob $job, Closure $next): ListenMessageModel
    {
        $this->sendMessageThroughPipes($job->getRabbitMQMessage(), $job->getQueue(), $job->getConnectionName());

        return $next($job);
    }

    /**
     * @param AMQPMessage $message
     * @param string $queue
     * @param string $connection
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function sendMessageThroughPipes(AMQPMessage $message, string $queue, string $connection): ListenMessageModel
    {
        $listenedMessage = $this->buildListenedMessage($message, $queue, $connection);

        try {
            $messageHandlerBuilder = $this->makeMessageHandleBuilder();

            /** @var ListenMessageModel $listenedMessage */
            $listenedMessage = Pipeline::send($listenedMessage)
                ->through([
                    app()->make(VerifyMessage::class),
                    $messageHandlerBuilder,
                    app()->make(MessageExhausted::class),
                    app()->make(StoreMessage::class),
                ])
                ->then($this->handleListenedMessage($messageHandlerBuilder));

            $message->ack();

            return $listenedMessage;
        } catch (SkipListenMessageThrowable|ValidationException $ex) {
            $this->skipMessage($message, $ex);
            throw $ex;
        } catch (MessageRetriesExhaustedException $ex) {
            $this->deadLetterMessage($listenedMessage, $message, $connection, $ex);
            throw $ex;
        } catch (Throwable $ex) {
            $this->retryMessage($listenedMessage, $message, $connection, $ex);
            throw $ex;
        }
    }

    /**
     * @return MessageHandlerBuilder
     * @throws BindingResolutionException
     */
    public function makeMessageHandleBuilder(): MessageHandlerBuilder
    {
        return app()->make(MessageHandlerBuilder::class);
    }

    /**
     * @param MessageHandlerBuilder $builder
     * @return callable
     */
    public function handleListenedMessage(MessageHandlerBuilder $builder): callable
    {
        return function (ListenMessageModel $message) use ($builder) {
            $handler = new HandleListenedMessage($builder->getHandler());

            return $handler->handle($message, fn() => $message);
        };
    }

    /**
     * @param AMQPMessage $message
     * @param string $queue
     * @param string $connection
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function buildListenedMessage(AMQPMessage $message, string $queue, string $connection): ListenMessageModel
    {
        try{
            /** @var ListenMessageModel $listenedMessage */
            $listenedMessage = new $this->listenMessageModel;

            $listenedMessage->properties = CastAMQPMessageProperties::fromAMQPMessage($message);
            $listenedMessage->payload = collect(json_decode($message->getBody(), true));
            $listenedMessage->metadata = collect([
                'connection' => $connection,
                'queue' => $queue,
                'exchange' => $message->getExchange(),
                'routing_key' => $message->getRoutingKey(),
                'size' => $message->getBodySize(),
            ]);

            $listenedMessage->uuid = $listenedMessage->properties->get('message_id');
            $listenedMessage->name = $listenedMessage->properties->get('type');

            return $listenedMessage;
        }
        catch (Throwable $ex){
            logger()->error("invalid message {$ex->getMessage()}", [
                'properties' => $message->get_properties(),
                'body' => $message->getBody(),
            ]);

            $message->reject(false);

            throw $ex;
        }
    }

    /**
     * @param AMQPMessage $message
     * @param Throwable $ex
     * @return void
     */
    protected function skipMessage(AMQPMessage $message, Throwable $ex): void
    {
        $reason = class_basename($ex);
        logger()->info("message skipped $reason: {$ex->getMessage()}", $message->get_properties());

        $message->ack();
    }

    /**
     * @param ListenMessageModel $listenedMessage
     * @param AMQPMessage $message
     * @param string $connection
     * @param Throwable $ex
     * @return void
     */
    protected function retryMessage(
        ListenMessageModel $listenedMessage,
        AMQPMessage $message,
        string $connection,
        Throwable $ex
    ): void
    {
        $reason = class_basename($ex);
        logger()->info(
            "retryMessage, $reason: {$ex->getMessage()}",
            $listenedMessage->properties->toArray()
        );

        $listenedMessage->setAsFailed($ex);
        $listenedMessage->save();

        if (RabbitMQMessage::canRetryMessages($connection)) {
            $listenedMessage->delete();
            RabbitMQMessage::retryMessage($listenedMessage, $ex);
        }

        $message->nack();
    }

    /**
     * @param ListenMessageModel $listenedMessage
     * @param AMQPMessage $message
     * @param string $connection
     * @param Throwable $ex
     * @return void
     * @throws MessageAlreadyRegisterException
     * @throws BindingResolutionException
     */
    protected function deadLetterMessage(
        ListenMessageModel $listenedMessage,
        AMQPMessage $message,
        string $connection,
        Throwable $ex
    ): void
    {
        $reason = class_basename($ex);
        logger()->info(
            "deadLetterMessage, $reason: {$ex->getMessage()}",
            $listenedMessage->properties->toArray()
        );

        if(!RabbitMQMessage::canDeadLetterMessages($connection)){
            /** @var StoreMessage $storeMessage */
            $storeMessage = app()->make(StoreMessage::class);
            $storeMessage->storeMessage($listenedMessage);

            $listenedMessage->setAsFailed();
            $listenedMessage->save();
        }
        else{
            RabbitMQMessage::deadLetterMessage($listenedMessage);
        }

        $message->reject(false);
    }
}
