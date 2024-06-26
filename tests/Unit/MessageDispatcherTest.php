<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use SlothDevGuy\RabbitMQMessages\DispatchMessage;
use SlothDevGuy\RabbitMQMessages\Jobs\DispatchMessagesJob;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\Enums\DispatchMessageStatusEnum;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessagesServiceProvider;
use SlothDevGuy\RabbitMQMessages\Services\MessageDispatcher;
use SlothDevGuy\RabbitMQMessagesTests\TestCase;

/**
 * Class MessageDispatcherTest
 * @package SlothDevGuy\RabbitMQMessagesTests\Unit
 */
class MessageDispatcherTest extends TestCase
{
    public function testDispatchMessage(): void
    {
        Queue::fake();
        Config::set('rabbitmq-messages.models.dispatch_message', get_class($this->mockDispatchMessageModel()));
        $dispatcher = new MessageDispatcher();
        $message = $dispatcher->dispatchMessage($name = 'foo-event', $payload = collect(['foo-key' => 'foo-value']));
        $this->assertNotNull($uuid = $message->uuid);
        $this->assertEquals($name, $message->name);
        $this->assertEquals(DispatchMessageStatusEnum::CREATED, $message->status);

        $this->assertNotNull($message->properties->get('app_id'));
        $this->assertEquals($name, $message->properties->get('type'));
        $this->assertNull($message->properties->get('timestamp'));
        $this->assertEquals($uuid, $message->properties->get('message_id'));

        $diff = $payload->diff($message->payload);
        $this->assertTrue($diff->isEmpty());
        $this->assertEquals($uuid, $message->payload->get('uuid'));
        $this->assertNull($message->payload->get('fired_at'));

        $this->assertEquals('rabbitmq', $message->metadata->get('connection'));
        Queue::assertPushed(DispatchMessagesJob::class);
    }

    public function testDispatchMessageFacade(): void
    {
        Queue::fake();
        Config::set('rabbitmq-messages.models.dispatch_message', get_class($this->mockDispatchMessageModel()));
        $this->app->register(RabbitMQMessagesServiceProvider::class);

        $message = RabbitMQMessage::dispatchMessage($name = 'foo-event', collect(['foo-key' => 'foo-value']));
        $this->assertNotNull($message->uuid);
        $this->assertEquals($name, $message->name);

        Queue::assertPushed(DispatchMessagesJob::class);
    }

    public function testDispatchMockMessage(): void
    {
        Queue::fake();
        $connection = 'test-connection';
        Config::set('rabbitmq-messages.models.dispatch_message', get_class($this->mockDispatchMessageModel()));
        Config::set("queue.connections.$connection", config('queue.connections.rabbitmq'));
        $this->app->register(RabbitMQMessagesServiceProvider::class);

        $mockMessage = $this->mockDispatchMessage([], $connection);
        $message = RabbitMQMessage::dispatchMessage($mockMessage);
        $this->assertNotNull($message->uuid);
        $this->assertEquals($mockMessage->getName(), $message->name);
        $this->assertEquals($connection, $message->metadata->get('connection'));

        Queue::assertPushed(DispatchMessagesJob::class);
    }

    /**
     * Mocks an instance of DispatchMessageModel.
     *
     * @return DispatchMessageModel The mocked instance of DispatchMessageModel.
     */
    protected function mockDispatchMessageModel(): DispatchMessageModel
    {
        return new class extends DispatchMessageModel {
            public function save(array $options = [])
            {

            }
        };
    }

    /**
     * @param array $payload
     * @param string $connection
     * @return DispatchMessage
     */
    protected function mockDispatchMessage(array $payload, string $connection): DispatchMessage
    {
        return new class($payload, $connection) extends DispatchMessage
        {
            public function __construct(array $payload, string $connection)
            {
                $name = 'mock-message';
                $payload = collect($payload);
                parent::__construct($name, $payload, $connection);
            }
        };
    }
}
