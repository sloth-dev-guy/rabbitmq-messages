<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class DispatchMessage
 * @package SlothDevGuy\RabbitMQMessages
 */
class DispatchMessage
{
    /**
     * The app_id that is sending this message
     *
     * @var string
     */
    protected string $appID;

    /**
     * The uuid for this message
     *
     * @var UuidInterface
     */
    protected UuidInterface $uuid;

    /**
     * The message properties and headers
     *
     * @var Collection
     */
    protected Collection $properties;

    /**
     * The message metadata to be stored
     *
     * @var Collection
     */
    protected Collection $metadata;

    /**
     * The message configurations
     *
     * @var array
     */
    protected array $configurations;

    /**
     * @param string $name
     * @param Enumerable|Collection $payload
     * @param string|null $connection
     */
    public function __construct(
        protected string $name,
        protected Enumerable|Collection $payload,
        protected string|null $connection = null,
    )
    {
        $this->connection = $this->connection ?? 'rabbitmq';
        $this->configurations = config("queue.connections.{$this->connection}");

        $this->uuid = static::buildUuid();
        $this->appID = $this->configurations['app_id'];

        $this->buildProperties();
        $this->buildPayload();
        $this->buildMetadata();
    }

    /**
     * Generic getter
     *
     * @return string
     */
    public function getAppID(): string
    {
        return $this->appID;
    }

    /**
     * Generic getter
     *
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * Generic getter
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the properties of the RabbitMQ message
     *
     * @return Collection
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * Get the payload of the RabbitMQ message
     *
     * @return Collection
     */
    public function getPayload(): Collection
    {
        return $this->payload;
    }

    /**
     * Get the metadata of the message
     *
     * @return Collection
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }

    /**
     * Build properties for RabbitMQ message
     *
     * @return void
     */
    protected function buildProperties(): void
    {
        $this->properties = collect([
            'app_id' => $this->getAppID(),
            'type' => $this->name,
            'timestamp' => null,
            'message_id' => $this->uuid->toString(),
            'content_type' => data_get($this->configurations, 'content_type', 'application/json'),
            'delivery_mode' => data_get($this->configurations, 'delivery_mode', AMQPMessage::DELIVERY_MODE_PERSISTENT),
            //'priority' => null,
            //'correlation_id' => null,
        ]);
    }

    /**
     * Build the payload for the RabbitMQ message
     *
     * @return void
     */
    protected function buildPayload(): void
    {
        $payload = [
            'app_id' => $this->getAppID(),
            'uuid' => $this->uuid->toString(),
            'fired_at' => null,
        ];

        $this->payload = collect($payload)
            ->merge($this->payload)
            ->merge($payload);
    }

    /**
     * Build metadata for RabbitMQ message
     *
     * @return void
     */
    protected function buildMetadata(): void
    {
        $this->metadata = collect([
            'connection' => $this->connection,
            'exchange' => data_get($this->configurations, 'options.queue.exchange', 'amq.fanout'),
            'exchange_type' => data_get($this->configurations, 'options.queue.exchange_type', 'fanout'),
            'routing_key' => data_get($this->configurations, 'options.queue.exchange_routing_key', ''),
        ]);
    }

    /**
     * Build a UUID using version 4
     *
     * @return UuidInterface
     */
    public static function buildUuid(): UuidInterface
    {
        return Uuid::uuid4();
    }
}
