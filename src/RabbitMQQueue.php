<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Support\Arr;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue as BaseRabbitMQQueue;

/**
 * Class RabbitMQQueue
 * @package SlothDevGuy\RabbitMQMessages
 */
class RabbitMQQueue extends BaseRabbitMQQueue
{
    /**
     * @param DispatchMessageModel $dispatchMessage
     * @param array $options
     * @return array
     */
    public static function getPublishProperties(DispatchMessageModel $dispatchMessage, array $options): array
    {
        $attempts = Arr::get($options, 'attempts') ?: 0;

        $destination = Arr::get($options, 'destination', $dispatchMessage->metadata->get('routing_key'));
        $exchange = Arr::get($options, 'exchange', $dispatchMessage->metadata->get('exchange'));
        $exchangeType = Arr::get($options, 'exchange_type', $dispatchMessage->metadata->get('exchange_type'));

        return [$destination, $exchange, $exchangeType, $attempts];
    }

    /**
     * @param DispatchMessageModel $dispatchMessage
     * @param array $options
     * @return DispatchMessageModel
     * @throws AMQPProtocolChannelException
     */
    public function dispatchMessage(DispatchMessageModel $dispatchMessage, array $options = []): DispatchMessageModel
    {
        [$destination, $exchange, $exchangeType] = static::getPublishProperties($dispatchMessage, $options);
        $this->declareDestination($destination, $exchange, $exchangeType);

        $dispatchMessage->setDispatchedAt();
        $dispatchMessage->save();

        $message = $this->makeAMPQMessageFrom($dispatchMessage);
        $this->publishBasic($message, $exchange, $destination, true);

        return $dispatchMessage;
    }

    /**
     * @param DispatchMessageModel $dispatchMessage
     * @return AMQPMessage
     */
    public static function makeAMPQMessageFrom(DispatchMessageModel $dispatchMessage): AMQPMessage
    {
        $payload = $dispatchMessage->payload->toJson(JSON_THROW_ON_ERROR);

        return new AMQPMessage($payload, $dispatchMessage->properties->toArray());
    }

    /**
     * @param ListenMessageModel $message
     * @param array{
     *     retry_queue: string,
     *     retry_exchange: string,
     *     retry_exchange_type: string,
     *     retry_exchange_delay: int,
     *     retry_exchange_routing_key: string,
     * } $configurations
     * @return ListenMessageModel
     * @throws AMQPProtocolChannelException
     */
    public function retryMessage(ListenMessageModel $message, array $configurations): ListenMessageModel
    {
        $this->declareDestination('', $configurations['retry_exchange'], $configurations['retry_exchange_type']);

        //$channel->queue_bind('my-queue', 'my-exchange', 'my-routing-key');
        // Create a queue for amq.direct publishing.
        if($this->isQueueDeclared($configurations['retry_queue'])) {
            $this->declareQueue($configurations['retry_queue'], true, false, [
                'x-dead-letter-exchange' => $message->metadata->get('exchange'),
                'x-dead-letter-routing-key' => $message->metadata->get('routing_key'),
                'x-message-ttl' => $configurations['retry_exchange_delay'],
            ]);

            $this->bindQueue(
                $configurations['retry_queue'],
                $configurations['retry_exchange'],
                $configurations['retry_exchange_routing_key']
            );
        }

        $this->publishBasic(
            $this->makeAMQPMessageFromListenedMessage($message),
            $configurations['retry_exchange'],
            $configurations['retry_exchange_routing_key'],
            true
        );

        return $message;
    }

    /**
     * @param ListenMessageModel $message
     * @param array{
     *     dead_letter_queue: string,
     *     dead_letter_exchange: string,
     *     dead_letter_exchange_type: string,
     *     dead_letter_exchange_routing_key: string,
     * } $configurations
     * @return ListenMessageModel
     * @throws AMQPProtocolChannelException
     */
    public function deadLetterMessage(ListenMessageModel $message, array $configurations): ListenMessageModel
    {
        $this->declareDestination('', $configurations['dead_letter_exchange'], $configurations['dead_letter_exchange_type']);

        if($this->isQueueDeclared($configurations['dead_letter_queue'])) {
            $this->declareQueue($configurations['dead_letter_queue']);

            $this->bindQueue(
                $configurations['dead_letter_queue'],
                $configurations['dead_letter_exchange'],
                $configurations['dead_letter_exchange_type']
            );
        }

        $this->publishBasic(
            $this->makeAMQPMessageFromListenedMessage($message),
            $configurations['dead_letter_exchange'],
            $configurations['dead_letter_exchange_type'],
            true
        );

        return $message;
    }

    /**
     * @param ListenMessageModel $message
     * @return AMQPMessage
     */
    public static function makeAMQPMessageFromListenedMessage(ListenMessageModel $message): AMQPMessage
    {
        $payload = $message->payload->toJson(JSON_THROW_ON_ERROR);

        return new AMQPMessage($payload, $message->properties->toArray());
    }
}
