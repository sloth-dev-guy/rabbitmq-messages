<?php

namespace SlothDevGuy\RabbitMQMessages\Interfaces;

use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

/**
 * Interface MessageHandlerInterface
 * @package SlothDevGuy\RabbitMQMessages\Interfaces
 */
interface MessageHandlerInterface
{
    /**
     * Handle a new income rabbitmq message (the message is already register)
     *
     * @param Collection $payload
     * @param ListenMessageModel $message
     * @return void
     */
    public function handle(Collection $payload, ListenMessageModel $message): void;
}
