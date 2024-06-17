<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageRetriesExhaustedException;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

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
        $this->failIfMessageRetriesExhausted($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return void
     * @throws MessageRetriesExhaustedException
     */
    public function failIfMessageRetriesExhausted(ListenMessageModel $message): void
    {
        $redeliveryCount = $message->properties->get('redelivery_count', 1);

        if($redeliveryCount > $this->getMaxTries()){
            $message = "Message retries exhausted, retries[$redeliveryCount] > max-retries[{$this->getMaxTries()}]";
            throw new MessageRetriesExhaustedException($message);
        }
    }

    /**
     * @return int
     */
    public function getMaxTries(): int
    {
        return 3;
    }
}
