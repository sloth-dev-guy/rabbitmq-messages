<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use PhpAmqpLib\Message\AMQPMessage;
use SlothDevGuy\RabbitMQMessages\Models\Enums\ListenMessageStatusEnum;
use SlothDevGuy\RabbitMQMessages\Pipes\MessageListener;
use SlothDevGuy\RabbitMQMessagesTests\TestCase;
use Throwable;

class MessageListenerTest extends TestCase
{
    /**
     * @return void
     * @throws Throwable
     */
    public function testListenMessage(): void
    {
        MockMessageHandler::mockDBFacade();
        $message = $this->mockRabbitMQMessage(
            $properties = $this->validProperties(),
            $this->validMessage($properties),
        );

        Config::set("queue.message_handlers.{$properties['type']}", MockMessageHandler::class);

        $registerMessage = new MessageListener(MockMessageHandler::mockListenMessageModel());
        $message = $registerMessage->sendMessageThroughPipes($message, 'foo-queue', 'foo-connection');

        $this->assertEquals($properties['app_id'], $message->properties->get('app_id'));
        $this->assertEquals($properties['message_id'], $message->uuid);
        $this->assertEquals($properties['type'], $message->name);
        $this->assertEquals(ListenMessageStatusEnum::PROCESSED, $message->status);

        $this->assertNotNull($message->properties->get('app_id'));
        $this->assertNotNull($message->properties->get('timestamp'));
    }

    public function testMessageAlreadyRegisterException(): void
    {

    }

    public function testInvalidMessageException(): void
    {

    }

    public function testRetryMessageForSaveException(): void
    {

    }

    public function testRetryMessageForFindException(): void
    {

    }

    /**
     * @param array $properties
     * @param string $message
     * @return AMQPMessage
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function mockRabbitMQMessage(array $properties, string $message): AMQPMessage
    {
        return $this->mock(AMQPMessage::class, function(MockInterface $mock) use ($properties, $message) {
            $mock->expects('getRoutingKey')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturn('');

            $mock->expects('getExchange')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturn('fanout');

            $mock->expects('getBodySize')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturn(strlen($message));

            $mock->expects('get_properties')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturnUsing(fn() => $properties);

            $mock->expects('getBody')
                ->zeroOrMoreTimes()
                ->withAnyArgs()
                ->andReturn($message);

            $mock->expects('ack')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturn();

            $mock->expects('nack')
                ->zeroOrMoreTimes()
                ->withNoArgs()
                ->andReturn();
        });
    }

    protected function validProperties(): array
    {
        return [
            'app_id' => fake()->domainName,
            'type' => 'foo-bar.test.event_dispatched',
            'timestamp' => fake()->dateTime->getTimestamp(),
            'message_id' => fake()->uuid,
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];
    }

    protected function validMessage(array $properties): string
    {
        return json_encode([
            'app_id' => $properties['app_id'],
            'uuid' => $properties['message_id'],
            'fired_at' => Carbon::createFromTimestamp($properties['timestamp'])->toIso8601String(),
        ]);
    }
}
