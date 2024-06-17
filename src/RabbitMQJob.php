<?php

namespace SlothDevGuy\RabbitMQMessages;

use SlothDevGuy\RabbitMQMessages\Pipes\MessageListener;
use Throwable;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseRabbitMQJob;

class RabbitMQJob extends BaseRabbitMQJob
{
    public function getName()
    {
        return data_get($this->getRabbitMQMessage()->get_properties(), 'type');
    }

    public function getJobId()
    {
        return data_get($this->getRabbitMQMessage()->get_properties(), 'message_id');
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function fire(): void
    {
        /** @var MessageListener $listener */
        $listener = app()->make(MessageListener::class);
        $listener->sendMessageThroughPipes($this->getRabbitMQMessage(), $this->getQueue(), $this->getConnectionName());
        $this->deleted = true;
    }
}
