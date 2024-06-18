<?php

namespace SlothDevGuy\RabbitMQMessages\Services;

use Illuminate\Support\Facades\Queue;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageResilientInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\RabbitMQQueue;
use Throwable;

/**
 * Class MessageResilienceService
 * @package SlothDevGuy\SimpleRabbitMQ\Services
 */
class MessageResilient implements MessageResilientInterface
{
    /**
     * @param string|null $connection
     * @return bool
     */
    public function canRetry(string $connection = null): bool
    {
        $configurations = $this->getRetryConfiguration($connection ?? 'rabbitmq');

        return $configurations['retry_queue']
            && $configurations['retry_exchange']
            && $configurations['retry_exchange_type']
            && $configurations['retry_exchange_routing_key'];
    }

    /**
     * @param ListenMessageModel $message
     * @param Throwable $reason
     * @param string|null $connection
     * @return ListenMessageModel
     * @throws AMQPProtocolChannelException
     */
    public function retry(ListenMessageModel $message, Throwable $reason, string $connection = null): ListenMessageModel
    {
        $connection = $connection ?? $message->getConnection();
        if(!$this->canRetry($connection)){
            return $message;
        }

        $configurations = $this->getRetryConfiguration($connection);

        /** @var RabbitMQQueue $rabbitmq */
        $rabbitmq = Queue::connection($connection);
        $this->incrementRedeliveryCount($message, $reason);
        $rabbitmq->retryMessage($message, $configurations);

        logger()->info('message-retried', $message->toArray());

        return $message;
    }

    /**
     * @param string|null $connection
     * @return bool
     */
    public function canDeadLetter(string $connection = null): bool
    {
        $configurations = $this->getDeadLetterConfiguration($connection ?? 'rabbitmq');

        return
            $configurations['dead_letter_queue']
            && $configurations['dead_letter_exchange']
            && $configurations['dead_letter_exchange_type']
            && $configurations['dead_letter_exchange_routing_key'];
    }

    /**
     * @param ListenMessageModel $message
     * @param string|null $connection
     * @return ListenMessageModel
     * @throws AMQPProtocolChannelException
     */
    public function deadLetter(ListenMessageModel $message, string $connection = null): ListenMessageModel
    {
        $connection = $connection ?? $message->getConnection();
        if(!$this->canDeadLetter($connection)){
            return $message;
        }

        $configurations = $this->getDeadLetterConfiguration($connection);
        /** @var RabbitMQQueue $rabbitmq */
        $rabbitmq = Queue::connection($connection);
        $rabbitmq->deadLetterMessage($message, $configurations);

        logger()->info('message-dead-letter', $message->toArray());

        return $message;
    }

    /**
     * @param string $connection
     * @return array
     */
    public function getRetryConfiguration(string $connection): array
    {
        return [
            'retry_queue' => config("queue.connections.$connection.retry_queue"),
            'retry_exchange' => config("queue.connections.$connection.retry_exchange"),
            'retry_exchange_type' => config("queue.connections.$connection.retry_exchange_type"),
            'retry_exchange_delay' => config("queue.connections.$connection.retry_exchange_delay"),
            'retry_exchange_routing_key' => config("queue.connections.$connection.retry_exchange_routing_key"),
        ];
    }

    /**
     * @param string $connection
     * @return array
     */
    public function getDeadLetterConfiguration(string $connection): array
    {
        return [
            'dead_letter_queue' => config("queue.connections.$connection.dead_letter_queue"),
            'dead_letter_exchange' => config("queue.connections.$connection.dead_letter_exchange"),
            'dead_letter_exchange_type' => config("queue.connections.$connection.dead_letter_exchange_type"),
            'dead_letter_exchange_routing_key' => config("queue.connections.$connection.dead_letter_exchange_routing_key"),
        ];
    }

    /**
     * @param ListenMessageModel $message
     * @param Throwable $reason
     * @return void
     */
    protected function incrementRedeliveryCount(ListenMessageModel $message, Throwable $reason): void
    {
        $message->properties->put('redelivery_count', $message->properties->get('redelivery_count', 1) + 1);
        $exceptions = json_decode($message->properties->get('exceptions', ''));
        $exceptions[] = [
            'reason' => class_basename($reason),
            'message' => $reason->getMessage(),
            'file' => $reason->getFile(),
            'line' => $reason->getLine(),
            'failed_at' => now()->toIso8601String(),
        ];
        $message->properties->put('exceptions', json_encode($exceptions));
    }
}
