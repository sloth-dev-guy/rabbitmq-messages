<?php

namespace SlothDevGuy\RabbitMQMessages\Services;

use Illuminate\Support\Enumerable;
use SlothDevGuy\RabbitMQMessages\DispatchMessage;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageDispatcherInterface;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageResilientInterface;
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
        protected MessageResilientInterface  $resilient,
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

    /**
     * Determines whether a specific connection can retry failed messages.
     *
     * @param string|null $connection The connection to check. If not provided, the default connection will be used.
     * @return bool Returns true if the connection can retry failed messages, otherwise returns false.
     */
    public function canRetryMessages(string $connection = null): bool
    {
        return $this->resilient->canRetry($connection);
    }

    /**
     * Retries a failed message.
     *
     * @param ListenMessageModel $message The message being retried.
     * @param Throwable $reason The reason for the retry.
     * @param string|null $connection The rabbitmq connection name. If not provided, the default connection will be used.
     *
     * @return ListenMessageModel The retried message.
     */
    public function retryMessage(ListenMessageModel $message, Throwable $reason, string $connection = null): ListenMessageModel
    {
        return $this->resilient->retry($message, $reason, $connection);
    }

    /**
     * Determine if the specified connection can dead letter a message
     *
     * @param string|null $connection The connection to check. If not provided, the default connection will be used.
     * @return bool Returns true if the connection can dead letter messages, false otherwise.
     */
    public function canDeadLetterMessages(string $connection = null): bool
    {
        return $this->resilient->canDeadLetter($connection);
    }

    /**
     * Moves a message to the dead letter exchange.
     *
     * @param ListenMessageModel $message The message to be moved to the dead letter exchange.
     * @param string|null $connection The rabbitmq connection to be used. If not provided, the default connection will be used.
     * @return ListenMessageModel The updated message with the dead letter information.
     */
    public function deadLetterMessage(ListenMessageModel $message, string $connection = null): ListenMessageModel
    {
        return $this->resilient->deadLetter($message, $connection);
    }
}
