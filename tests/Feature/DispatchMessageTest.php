<?php

namespace Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
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

    public function testMessageDispatchFailed(): void
    {
        $this->expectException(AMQPIOException::class);
        $this->setInvalidConnection();
        $this->app->register(RabbitMQMessagesServiceProvider::class);
        $payload = collect(['foo-key' => 'foo-value']);
        RabbitMQMessage::dispatchMessage('foo-event', $payload, 'invalid');
    }

    protected function setInvalidConnection(): void
    {
        Config::set('queue.connections.invalid', [
            'driver' => 'rabbitmq',
            'after_commit' => true,
            'hosts' => [
                [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'foo',
                    'password' => 'bar',
                    'vhost' => '/',
                ],
            ],
            'app_id' => 'invalid-app',
            'worker' => RabbitMQQueue::class,
        ]);
    }
}
