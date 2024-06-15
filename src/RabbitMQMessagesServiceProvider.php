<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
//use SlothDevGuy\RabbitMQMessages\Interface\MessagePublisherInterface;
//use SlothDevGuy\RabbitMQMessages\Interface\MessageResilientInterface;
//use SlothDevGuy\RabbitMQMessages\Services\HelperClass;
//use SlothDevGuy\RabbitMQMessages\Services\MessagePublisher;
//use SlothDevGuy\RabbitMQMessages\Services\MessageResilienceService;

/**
 * Class RabbitMQMessagesServiceProvider
 * @package SlothDevGuy\RabbitMQMessages
 */
class RabbitMQMessagesServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
//        $this->app->bind(MessagePublisherInterface::class, MessagePublisher::class);
//        $this->app->bind(MessageResilientInterface::class, MessageResilienceService::class);
//
//        $helper = $this->app->make(HelperClass::class);
//        $this->app->instance(RabbitMQMessage::ACCESSOR, $helper);
    }

    public function boot(): void
    {
        $this->configurePublishing();
    }

    protected function configurePublishing(): void
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../database/migrations/2024_05_20_152036_create_rabbitmq_messages_tables.php' =>
                database_path('migrations/2024_05_20_152036_create_rabbitmq_messages_tables.php'),
        ], 'rabbitmq-messages-migrations');
    }
}
