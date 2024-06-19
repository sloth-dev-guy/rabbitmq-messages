<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageRetriesExhaustedException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use SlothDevGuy\RabbitMQMessages\Services\MessageResilient;

class MessageExhausted
{
    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws MessageRetriesExhaustedException
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        $this->failIfMessageTriesExhausted($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return void
     * @throws MessageRetriesExhaustedException
     */
    public function failIfMessageTriesExhausted(ListenMessageModel $message): void
    {
        $tries = data_get($message->properties, MessageResilient::getTriesKey(), 0);
        $maxTries = MessageResilient::getMaxTries();

        if($maxTries && $tries >= $maxTries){
            $message = "Message retries exhausted, retries[$tries] >= max-retries[$maxTries]";
            throw new MessageRetriesExhaustedException($message);
        }
    }
}
