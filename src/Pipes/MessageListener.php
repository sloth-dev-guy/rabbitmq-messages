<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Builders\FromListenMessage;
use SlothDevGuy\RabbitMQMessages\Builders\FromRabbitMQMessage;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageRetriesExhaustedException;
use SlothDevGuy\RabbitMQMessages\Interfaces\SkipListenMessageThrowable;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\Pipes\Resiliency\DeadLetterMessage;
use SlothDevGuy\RabbitMQMessages\Pipes\Resiliency\DispatchMessage;
use SlothDevGuy\RabbitMQMessages\Pipes\Resiliency\RetryMessage;
use Throwable;

class MessageListener
{
    /**
     * @param AMQPMessage $message
     * @param string $queue
     * @param string $connection
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function sendMessageThroughPipes(AMQPMessage $message, string $queue, string $connection): ListenMessageModel
    {
        $fromRabbitMQMessage = new FromRabbitMQMessage($message, $queue, $connection);
        $listenedMessage = $fromRabbitMQMessage->buildListenMessageModel(app(ListenMessageModel::class));

        try {
            /**
             * @var FromListenMessage $fromListenMessage
             * @var ListenMessageModel $listenedMessage
             */
            $listenedMessage = Pipeline::send($listenedMessage)
                ->through([
                    app()->make(MessageValidation::class),
                    $fromListenMessage = app()->make(FromListenMessage::class),
                    app()->make(VerifyMessageAlreadyRegister::class),
                    app()->make(MessageExhausted::class),
                    app()->make(StoreMessage::class),
                ])
                ->then(function (ListenMessageModel $listenedMessage) use ($fromListenMessage) {
                    $dispatchMessage = new DispatchMessage($fromListenMessage->getHandler());
                    $dispatchMessage->handle($listenedMessage);
                    return $listenedMessage;
                });

            $message->ack();

            return $listenedMessage;
        } catch (SkipListenMessageThrowable|ValidationException $ex) {
            $this->skipMessage($message, $ex);
            throw $ex;
        } catch (MessageRetriesExhaustedException $ex) {
            $deadLetterMessage = new DeadLetterMessage($listenedMessage, $message, $connection, $ex);
            $deadLetterMessage->handle();
            throw $ex;
        } catch (Throwable $ex) {
            $retryMessage = new RetryMessage($listenedMessage, $message, $connection, $ex);
            $retryMessage->handle();
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
}
