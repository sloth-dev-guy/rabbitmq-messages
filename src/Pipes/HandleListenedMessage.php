<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use Illuminate\Support\Facades\DB;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;
use Throwable;

class HandleListenedMessage
{
    protected ListenMessageModel $model;

    public function __construct(
        protected MessageHandlerInterface $handler,
    )
    {

    }

    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        $this->model = $this->handleMessage($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return ListenMessageModel
     * @throws Throwable
     */
    public function handleMessage(ListenMessageModel $message): ListenMessageModel
    {
        $connection = $message->getConnectionName();

        return DB::connection($connection)->transaction(function () use ($message) {
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
