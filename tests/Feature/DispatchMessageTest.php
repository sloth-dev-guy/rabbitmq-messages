<?php

namespace Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use SlothDevGuy\RabbitMQMessages\Models\Enums\DispatchMessageStatusEnum;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessage;
use SlothDevGuy\RabbitMQMessages\RabbitMQMessagesServiceProvider;
use SlothDevGuy\RabbitMQMessages\RabbitMQQueue;
use SlothDevGuy\RabbitMQMessagesTests\TestCase;

/**
 * Class DispatchMessageTest
 * @package Feature
 */
class DispatchMessageTest extends TestCase
{
    use DatabaseMigrations;

    public function testMessageDispatch(): void
    {
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $message = RabbitMQMessage::dispatchMessage('foo-event', collect(['foo-key' => 'foo-value']));
        $this->assertEquals(DispatchMessageStatusEnum::DISPATCHED, $message->fresh()->status);
    }

    public function testMessageDispatchFailedInvalidHost(): void
    {
        $this->expectException(AMQPIOException::class);
        $this->setInvalidConnection([
            'host' => 'invalid-host',
        ]);
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $payload = collect(['foo-key' => 'foo-value']);

        try{
            RabbitMQMessage::dispatchMessage('foo-event', $payload, 'invalid');
        } finally {
            $this->assertFailedDispatch();
        }
    }

    public function testMessageDispatchFailedInvalidUsernameAndPassword(): void
    {
        $this->expectException(AMQPConnectionClosedException::class);
        $this->setInvalidConnection([
            'host' => 'rabbitmq',
            'user' => 'invalid-username',
            'password' => 'invalid-password',
        ]);
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $payload = collect(['foo-key' => 'foo-value']);
        try{
            RabbitMQMessage::dispatchMessage('foo-event', $payload, 'invalid');
        } finally {
            $this->assertFailedDispatch();
        }
    }

    protected function setInvalidConnection(array $host): void
    {
        Config::set('queue.connections.invalid', [
            'driver' => 'rabbitmq',
            'after_commit' => true,
            'hosts' => [
                array_merge([
                    'host' => 'rabbitmq',
                    'port' => 5672,
                    'user' => 'username',
                    'password' => 'password',
                    'vhost' => '/',
                ], $host),
            ],
            'app_id' => 'invalid-app',
            'worker' => RabbitMQQueue::class,
        ]);
    }

    protected function assertFailedDispatch(): void
    {
        $this->assertDatabaseCount('dispatch_message', 1);
        $this->assertDatabaseHas('dispatch_message', [
            'status' => DispatchMessageStatusEnum::FAILED,
        ]);
    }
}
