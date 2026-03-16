<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use Illuminate\Validation\ValidationException;
use SlothDevGuy\RabbitMQMessages\Exceptions\InvalidRabbitMQMessageException;
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
        } catch (ValidationException $ex) {
            throw new InvalidRabbitMQMessageException($ex->getMessage(), $ex->getCode(), $ex);
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
