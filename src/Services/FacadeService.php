<?php

namespace SlothDevGuy\RabbitMQMessages\Services;

use Illuminate\Support\Enumerable;
use SlothDevGuy\RabbitMQMessages\DispatchMessage;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageDispatcherInterface;
//use SlothDevGuy\RabbitMQMessages\Interface\MessageResilientInterface;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

/**
 * Class HelperClass
 * @package SlothDevGuy\RabbitMQMessages\Services
 */
class FacadeService
{
    public function __construct(
        protected MessageDispatcherInterface $dispatcher,
        //protected MessageResilientInterface  $resilient,
    )
    {

    }

    /**
     * Dispatches a message to be processed by the Laravel queue worker.
     *
     * @param string|DispatchMessage $message The message to be dispatched.
     * @param Enumerable|null $payload The payload data to be passed along with the message.
     * @param string|null $connection The name of the queue connection to use.
     *
     * @return DispatchMessageModel The model representing the dispatched message.
     */
    public function dispatchMessage(string|DispatchMessage $message, Enumerable $payload = null, string $connection = null): DispatchMessageModel
    {
        return $this->dispatcher->dispatchMessage($message, $payload, $connection);
    }

    public function canRetryMessages(string $connection = null): bool
    {
        //return $this->resilient->canRetry($connection);
    }

    public function retryMessage(ListenMessageModel $message, Throwable $reason, string $connection = null): ListenMessageModel
    {
        //return $this->resilient->retry($message, $reason, $connection);
    }

    public function canDeadLetterMessages(string $connection = null): bool
    {
        //return $this->resilient->canDeadLetter($connection);
    }

    public function deadLetterMessage(ListenMessageModel $message, string $connection = null): ListenMessageModel
    {
        //return $this->resilient->deadLetter($message, $connection);
    }
}
