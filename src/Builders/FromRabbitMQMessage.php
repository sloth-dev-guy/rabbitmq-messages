<?php

namespace SlothDevGuy\RabbitMQMessages\Builders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\RabbitMQProperties;
use Throwable;

class FromRabbitMQMessage
{
    /**
     * @param AMQPMessage $message
     * @param string $queue
     * @param string $connection
     */
    public function __construct(
        protected AMQPMessage $message,
        protected string $queue,
        protected string $connection
    )
    {
        $this->setRealConnection();
    }

    /**
     * @param ListenMessageModel $listenMessageModel
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function buildListenMessageModel(ListenMessageModel $listenMessageModel): ListenMessageModel
    {
        try{
            $listenedMessage = new $listenMessageModel;
            $listenedMessage->properties = RabbitMQProperties::fromAMQPMessage($this->message);
            $listenedMessage->payload = collect(json_decode($this->message->getBody(), true));
            $listenedMessage->metadata = collect([
                'connection' => $this->connection,
                'queue' => $this->queue,
                'exchange' => $this->message->getExchange(),
                'routing_key' => $this->message->getRoutingKey(),
                'size' => $this->message->getBodySize(),
            ]);
            $listenedMessage->uuid = $listenedMessage->properties->get('message_id');
            $listenedMessage->name = $listenedMessage->properties->get('type');
            return $listenedMessage;
        }
        catch (Throwable $ex){
            logger()->error("invalid message {$ex->getMessage()}", [
                'properties' => $this->message->get_properties(),
                'body' => $this->message->getBody(),
            ]);
            $this->message->reject(false);
            throw $ex;
        }
    }

    /**
     * @return void
     */
    protected function setRealConnection(): void
    {
        $keys = array_keys(config('queue.connections'));
        $keys = array_filter($keys, fn(string $key) => Str::startsWith($key, $this->connection));
        $exchange = $this->message->getExchange();
        $routingKey = $this->message->getRoutingKey();
        $this->connection = Arr::first($keys, function (string $connection) use ($exchange, $routingKey) {
            $expectedExchange = config("queue.connections.{$connection}.options.queue.exchange");
            $expectedRoutingKey = config("queue.connections.{$connection}.options.queue.exchange_routing_key");
            return $expectedExchange === $exchange && $expectedRoutingKey === $routingKey;
        }, $this->connection);
    }
}
