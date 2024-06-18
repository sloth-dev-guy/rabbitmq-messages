<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Feature\Mocks;

use Exception;
use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class MockFailedMessageHandler implements MessageHandlerInterface
{
    /**
     * @param Collection $payload
     * @param ListenMessageModel $message
     * @return void
     * @throws Exception
     */
    public function handle(Collection $payload, ListenMessageModel $message): void
    {
        $name = fake()->name;

        throw new Exception("failed message handler, guilty one: $name");
    }
}
