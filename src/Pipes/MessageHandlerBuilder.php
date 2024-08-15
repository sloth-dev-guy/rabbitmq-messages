<?php

namespace SlothDevGuy\RabbitMQMessages\Pipes;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use SlothDevGuy\RabbitMQMessages\Exceptions\MessageWithoutHandlerException;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageHandlerInterface;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class MessageHandlerBuilder
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
        $this->handler = $this->build($message);

        return $next($message);
    }

    /**
     * @param ListenMessageModel $message
     * @return MessageHandlerInterface
     * @throws BindingResolutionException
     * @throws MessageWithoutHandlerException
     */
    public function build(ListenMessageModel $message): MessageHandlerInterface
    {
        $handler = @config('rabbitmq-messages.message_handlers')[$message->name];

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
