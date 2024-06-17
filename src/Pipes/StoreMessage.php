<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageAlreadyRegisterException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class StoreMessage
{
    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws MessageAlreadyRegisterException
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        $this->storeMessage($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return ListenMessageModel
     * @throws MessageAlreadyRegisterException
     */
    public function storeMessage(ListenMessageModel $message): ListenMessageModel
    {
        VerifyMessage::failIfMessageAlreadyRegistered($message);
        $message->setAsQueued();
        $message->save();

        return $message;
    }
}
