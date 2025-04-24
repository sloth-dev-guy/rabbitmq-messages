<?php

namespace SlothDevGuy\RabbitMQMessages\Builders;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageWithoutHandlerException;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class FromListenMessage
{
    protected MessageHandlerInterface $handler;

    /**
     * @param ListenMessageModel $message
     * @param Closure $next
     * @return mixed
     * @throws MessageWithoutHandlerException
     * @throws BindingResolutionException
     */
    public function handle(ListenMessageModel $message, Closure $next): mixed
    {
        $this->handler = $this->buildMessageHandler($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return MessageHandlerInterface
     * @throws BindingResolutionException
     * @throws MessageWithoutHandlerException
     */
    public function buildMessageHandler(ListenMessageModel $message): MessageHandlerInterface
    {
        $handlers = config('rabbitmq-messages.message_handlers');
        $handler = @$handlers[$message->name] ?? Arr::first(array_filter(
            $handlers,
            fn($key) => Str::startsWith($key, '/'),
            ARRAY_FILTER_USE_KEY
        ), fn($handler, $pattern) => preg_match($pattern, $message->name));

        if(is_null($handler)){
            throw new MessageWithoutHandlerException("No handler for message: $message->name");
        }

        return app()->make($handler);
    }

    /**
     * @return MessageHandlerInterface
     */
    public function getHandler(): MessageHandlerInterface
    {
        return $this->handler;
    }
}
