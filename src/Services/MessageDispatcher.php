<?php

namespace SlothDevGuy\RabbitMQMessages\Services;

use Illuminate\Support\Enumerable;
use SlothDevGuy\RabbitMQMessages\DispatchMessage;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageDispatcherInterface;
use SlothDevGuy\RabbitMQMessages\Jobs\DispatchMessagesJob;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\Enums\DispatchMessageStatusEnum;

/**
 * Class MessagePublisher
 * @package SlothDevGuy\RabbitMQMessages\Services
 */
class MessageDispatcher implements MessageDispatcherInterface
{
    protected DispatchMessageModel $dispatchMessage;

    /**
     * @param string|null $connection
     */
    public function __construct(
        protected string|null $connection = null,
    )
    {
        $class = config('rabbitmq-messages.models.dispatch_message', DispatchMessageModel::class);
        $this->dispatchMessage = app($class);
    }

    /**
     * @inheritdoc
     * @param DispatchMessage|string $message
     * @param Enumerable|null $payload
     * @param string|null $connection
     * @return DispatchMessageModel
     */
    public function dispatchMessage(DispatchMessage|string $message, Enumerable $payload = null, string $connection = null): DispatchMessageModel
    {
        if(is_string($message)){
            $message = new DispatchMessage($message, $payload, $connection? : $this->connection);
        }

        /** @var DispatchMessageModel $dispatchMessage */
        $dispatchMessage = new $this->dispatchMessage;
        $dispatchMessage->uuid = $message->getUuid()->toString();
        $dispatchMessage->name = $message->getName();
        $dispatchMessage->status = DispatchMessageStatusEnum::CREATED;
        $dispatchMessage->properties = $message->getProperties();
        $dispatchMessage->payload = $message->getPayload();
        $dispatchMessage->metadata = $message->getMetadata();
        $dispatchMessage->save();

        DispatchMessagesJob::dispatch($dispatchMessage);

        return $dispatchMessage;
    }
}
