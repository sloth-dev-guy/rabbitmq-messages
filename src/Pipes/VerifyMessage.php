<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageAlreadyRegisterException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

class VerifyMessage
{
    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        validator([
            'properties' => $message->properties->toArray(),
            'payload' => $message->properties->toArray(),
            'metadata' => $message->properties->toArray(),
        ], static::rules())->validate();

        static::failIfMessageAlreadyRegistered($message);

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

    /**
     * @param ListenMessageModel $message
     * @return void
     * @throws MessageAlreadyRegisterException
     */
    public static function failIfMessageAlreadyRegistered(ListenMessageModel $message): void
    {
        if($exists = $message::findByUuid($message->uuid)){
            throw new MessageAlreadyRegisterException("Message already registered[$exists->id:$exists->uuid]");
        }
    }
}
