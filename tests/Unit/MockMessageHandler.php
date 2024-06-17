<?php

namespace SlothDevGuy\RabbitMQMessagesTests\Unit;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

/**
 * Class MockMessageHandler
 * @package SlothDevGuy\RabbitMQMessagesTests\Unit
 */
class MockMessageHandler implements MessageHandlerInterface
{
    /**
     * @inheritdoc
     * @param Collection $payload
     * @param ListenMessageModel $message
     * @return void
     */
    public function handle(Collection $payload, ListenMessageModel $message): void
    {
        logger()->info('payload', $payload->toArray());
        logger()->info('properties', $message->properties->toArray());
        logger()->info('metadata', $message->metadata->toArray());
    }

    /**
     * Mocks the DB facade using Laravel mocking capabilities.
     *
     * @return void
     */
    public static function mockDBFacade(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->withAnyArgs()
            ->andReturnUsing(fn() => new class
            {
                public function transaction(callable $callback): mixed
                {
                    return call_user_func_array($callback, []);
                }
            });
    }

    /**
     * @return ListenMessageModel
     */
    public static function mockListenMessageModel(): ListenMessageModel
    {
        return new class() extends ListenMessageModel {
            public static array $fails = [
                'save' => false,
                'delete' => false,
                'find' => false,
            ];

            /**
             * @return void
             * @noinspection PhpMissingReturnTypeInspection
             */
            public function save(array $options = [])
            {
                if(static::$fails['save'])
                    throw new QueryException('test-connection', 'select now()', [], new Exception());
            }

            /**
             * @return void
             * @noinspection PhpMissingReturnTypeInspection
             */
            public function delete()
            {
                if(static::$fails['delete'])
                    throw new QueryException('test-connection', 'select now()', [], new Exception());
            }

            /**
             * @param string $uuid
             * @return static|ListenMessageModel|null
             */
            public static function findByUuid(string $uuid): static|null
            {
                if(static::$fails['find'])
                    throw new QueryException('test-connection', 'select now()', [], new Exception());

                return null;
            }
        };
    }
}
