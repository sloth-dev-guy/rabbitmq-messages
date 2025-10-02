<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use SlothDevGuy\RabbitMQMessages\Exceptions\NonRetriableMessageException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

class MessageValidation
{
    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        try {
            validator([
                'properties' => $message->properties->toArray(),
                'payload' => $message->properties->toArray(),
                'metadata' => $message->properties->toArray(),
            ], static::rules())->validate();
        } catch (Throwable $ex) {
            throw new NonRetriableMessageException();
        }

        return $next($message);
    }

    public static function rules(): array
    {
        return [
            'properties' => ['required', 'array'],
            'properties.app_id' => ['required', 'string'],
            'properties.message_id' => ['required', 'string', 'uuid'],
            'properties.type' => ['required', 'string', 'min:5', 'max:255'],
            'payload' => ['required', 'array'],
            'metadata' => ['required', 'array'],
        ];
    }
}
