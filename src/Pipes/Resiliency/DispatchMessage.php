<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes\Resiliency;

use Closure;
use Illuminate\Support\Facades\DB;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

class DispatchMessage
{
    public function __construct(
        protected MessageHandlerInterface $handler,
    )
    {

    }

    /**
     * @param ListenMessageModel $message
     * @return void
     * @throws Throwable
     */
    public function handle(ListenMessageModel $message): void
    {
        $connection = $message->getConnectionName();

        DB::connection($connection)->transaction(function () use ($message) {
            $this->handler->handle($message->payload, $message);

            $message->setAsProcessed();
            $message->metadata = $message->metadata->merge([
                'handler' => get_class($this->handler),
            ]);
            $message->save();

            return $message;
        });
    }
}
