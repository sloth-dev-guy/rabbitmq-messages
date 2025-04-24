<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes\Resiliency;

use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\Pipes\StoreMessage;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use Throwable;

class DeadLetterMessage
{
    /**
     * @param ListenMessageModel $listenedMessage
     * @param AMQPMessage $message
     * @param string $connection
     * @param Throwable $ex
     */
    public function __construct(
        protected ListenMessageModel $listenedMessage,
        protected AMQPMessage $message,
        protected string $connection,
        protected Throwable $ex
    )
    {

    }

    /**
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->listenedMessage->getConnection()->transaction(function () {
            logger()->info('MessageListener::deadLetterMessage', [
                'reason' => class_basename($this->ex),
                'message' => $this->ex->getMessage(),
                'properties' => $this->listenedMessage->properties->toArray(),
            ]);

            if(!RabbitMQMessage::canDeadLetterMessages($this->connection)){
                /** @var StoreMessage $storeMessage */
                $storeMessage = app()->make(StoreMessage::class);
                $storeMessage->storeMessage($this->listenedMessage);

                $this->listenedMessage->setAsFailed();
                $this->listenedMessage->save();
            }
            else{
                RabbitMQMessage::deadLetterMessage($this->listenedMessage);
            }

            $this->message->reject(false);
        });
    }
}
