<?php

namespace SlothDevGuy\RabbitMQMessages\Interfaces;

use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

interface MessageResilientInterface
{
    /**
     * Determines whether a specific connection can retry failed messages.
     *
     * @param string|null $connection The connection to check. If not provided, the default connection will be used.
     * @return bool Returns true if the connection can retry failed messages, otherwise returns false.
     */
    public function canRetry(string $connection = null): bool;

    /**
     * Retries a failed message.
     *
     * @param ListenMessageModel $message The message being retried.
     * @param Throwable $reason The reason for the retry.
     * @param string|null $connection The rabbitmq connection name. If not provided, the default connection will be used.
     *
     * @return ListenMessageModel The retried message.
     */
    public function retry(ListenMessageModel $message, Throwable $reason, string $connection = null): ListenMessageModel;

    /**
     * Determine if the specified connection can dead letter a message
     *
     * @param string|null $connection The connection to check. If not provided, the default connection will be used.
     * @return bool Returns true if the connection can dead letter messages, false otherwise.
     */
    public function canDeadLetter(string $connection = null): bool;

    /**
     * Moves a message to the dead letter exchange.
     *
     * @param ListenMessageModel $message The message to be moved to the dead letter exchange.
     * @param string|null $connection The rabbitmq connection to be used. If not provided, the default connection will be used.
     * @return ListenMessageModel The updated message with the dead letter information.
     */
    public function deadLetter(ListenMessageModel $message, string $connection = null): ListenMessageModel;
}
