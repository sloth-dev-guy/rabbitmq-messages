<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Feature\Mocks;

use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Exceptions\NonRetriableMessageException;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class MockInvalidMessageHandler implements MessageHandlerInterface
{
    /**
     * @param Collection $payload
     * @param ListenMessageModel $message
     * @return void
     * @throws NonRetriableMessageException
     */
    public function handle(Collection $payload, ListenMessageModel $message): void
    {
        throw new NonRetriableMessageException("Invalid message $message->name that goes to the dead letter");
    }
}
