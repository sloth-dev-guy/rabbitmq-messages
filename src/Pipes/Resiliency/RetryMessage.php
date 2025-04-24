<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes\Resiliency;

use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use Throwable;

class RetryMessage
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
        $this->listenedMessage->getConnection()->transaction(function (){
            logger()->info('MessageListener::retryMessage', [
                'reason' => class_basename($this->ex),
                'message' => $this->ex->getMessage(),
                'properties' => $this->listenedMessage->properties->toArray(),
            ]);

            $this->listenedMessage->setAsFailed($this->ex);
            $this->listenedMessage->save();

            if (RabbitMQMessage::canRetryMessages($this->connection)) {
                $this->listenedMessage->delete();
                RabbitMQMessage::retryMessage($this->listenedMessage, $this->ex);
            }

            $this->message->nack();
        });
    }
}
