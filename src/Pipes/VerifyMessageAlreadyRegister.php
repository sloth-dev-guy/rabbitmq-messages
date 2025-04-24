<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use SlothDevGuy\RabbitMQMessages\Exceptions\MessageAlreadyRegisterException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Closure;

class VerifyMessageAlreadyRegister
{
    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws MessageAlreadyRegisterException
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        static::assert($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return void
     * @throws MessageAlreadyRegisterException
     */
    public static function assert(ListenMessageModel $message): void
    {
        if($exists = $message::findByUuid($message->uuid)){
            throw new MessageAlreadyRegisterException("Message already registered[$exists->id:$exists->uuid]");
        }
    }
}
