<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Feature\Mocks;

use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class MockMessageHandler implements MessageHandlerInterface
{
    /**
     * @param Collection $payload
     * @param ListenMessageModel $message
     * @return void
     */
    public function handle(Collection $payload, ListenMessageModel $message): void
    {
        logger()->info('message handle', [
            'name' => $message->name,
            'uuid' => $message->uuid,
        ]);
    }
}
