<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Facade;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\Services\FacadeService;
use Throwable;

/**
 * Class RabbitMQMessage
 * @package SlothDevGuy\RabbitMQMessages
 *
 * @method static DispatchMessageModel dispatchMessage(string|DispatchMessage $message, Enumerable $payload = null, string $connection = null)
 * @method static bool canRetryMessages(string $connection = null)
 * @method static ListenMessageModel retryMessage(ListenMessageModel $message, Throwable $reason, string $connection = null)
 * @method static bool canDeadLetterMessages(string $connection = null)
 * @method static ListenMessageModel deadLetterMessage(ListenMessageModel $message, string $connection = null)
 *
 * @see FacadeService
 */
class RabbitMQMessage extends Facade
{
    const ACCESSOR = 'rabbitmq-messages';

    protected static function getFacadeAccessor(): string
    {
        return static::ACCESSOR;
    }
}
