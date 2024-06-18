<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use SlothDevGuy\RabbitMQMessages\Models\Enums\ListenMessageStatusEnum;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessagesServiceProvider;
use SlothDevGuy\RabbitMQMessages\RabbitMQQueue;
use SlothDevGuy\RabbitMQMessagesTests\Feature\Mocks\MockFailedMessageHandler;
use SlothDevGuy\RabbitMQMessagesTests\Feature\Mocks\MockMessageHandler;
use SlothDevGuy\RabbitMQMessagesTests\TestCase;

class ListenMessageTest extends TestCase
{
    use DatabaseMigrations;

    public function testMessageListen(): void
    {
        $message = 'test-message-listen';
        Config::set("queue.message_handlers.$message", MockMessageHandler::class);
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $configurations = $this->declareQueue();

        $payload = collect(static::makeFakePayload());

        $message = RabbitMQMessage::dispatchMessage($message, $payload)->fresh();
        $this->artisan('rabbitmq:consume', [
            'connection' => 'rabbitmq',
            '--name' => $configurations['queue'],
            '--queue' => $configurations['queue'],
            '--once' => true,
        ])->assertOk();

        $this->assertDatabaseHas('listen_message', [
            'uuid' => $message->uuid,
        ]);

        $actualMessage = ListenMessageModel::findByUuid($message->uuid);
        $this->assertEquals(ListenMessageStatusEnum::PROCESSED, $actualMessage->status);
        $this->assertEquals($message->name, $actualMessage->name);
        $this->assertEquals($message->uuid, $actualMessage->uuid);
        $this->assertEmpty($message->payload->diff($actualMessage->payload));
    }

    public function testFailedMessageListen(): void
    {
        $message = 'test-message-listen';
        Config::set("queue.message_handlers.$message", MockFailedMessageHandler::class);
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $configurations = $this->declareQueue();

        $payload = collect(static::makeFakePayload());

        $message = RabbitMQMessage::dispatchMessage($message, $payload)->fresh();
        $this->artisan('rabbitmq:consume', [
            'connection' => 'rabbitmq',
            '--name' => $configurations['queue'],
            '--queue' => $configurations['queue'],
            '--once' => true,
        ])->assertOk();

        $this->assertDatabaseHas('listen_message', [
            'uuid' => $message->uuid,
        ]);

        $actualMessage = ListenMessageModel::findByUuid($message->uuid);
        $this->assertEquals(ListenMessageStatusEnum::FAILED, $actualMessage->status);
        $this->assertEquals($message->name, $actualMessage->name);
        $this->assertEquals($message->uuid, $actualMessage->uuid);
        $this->assertEmpty($message->payload->diff($actualMessage->payload));
        $this->assertNotEmpty($actualMessage->properties->get('exceptions'));
        $this->assertQueueIsEmpty($configurations['queue']);
    }

    protected static function makeFakePayload(): array
    {
        return [
            'name' => fake()->name,
            'last_name' => fake()->lastName,
            'birth_date' => fake()->date,
            'notes' => fake()->paragraph,
        ];
    }

    /**
     * @todo move this shit to a command rabbitmq-messages:queue-declare $connection
     * @param string $connection
     * @return array
     */
    protected function declareQueue(string $connection = 'rabbitmq'): array
    {
        $exchange = config("queue.connections.{$connection}.options.queue.exchange");
        $exchangeType = config("queue.connections.{$connection}.options.queue.exchange_type");
        $exchangeRoutingKey = config("queue.connections.{$connection}.options.queue.exchange_routing_key");
        $queue = config("queue.connections.{$connection}.queue");

        $this->artisan('rabbitmq:exchange-declare', [
            'name' => $exchange,
            'connection' => $connection,
            '--type' => $exchangeType,
        ])->assertOk();

        $this->artisan('rabbitmq:queue-declare', [
            'name' => $queue,
            'connection' => $connection,
        ])->assertOk();

        $this->artisan('rabbitmq:queue-bind', [
            'queue' => $queue,
            'exchange' => $exchange,
            'connection' => $connection,
            '--routing-key' => $exchangeRoutingKey,
        ])->assertOk();

        //@todo if you move this shit, erase this crap
        $this->artisan('rabbitmq:queue-purge', [
            'queue' => $queue,
            'connection' => $connection,
        ])->assertOk();

        return [
            'exchange' => $exchange,
            'exchange_type' => $exchangeType,
            'exchange_routing_key' => $exchangeRoutingKey,
            'queue' => $queue,
        ];
    }

    /**
     * @param string $queue
     * @param string $connection
     * @return void
     */
    protected function assertQueueIsEmpty(string $queue, string $connection = 'rabbitmq'): void
    {
        /** @var RabbitMQQueue $rabbitmq */
        $rabbitmq = Queue::connection($connection);

        $this->assertNull($rabbitmq->getChannel()->basic_get($queue, true));
    }
}
