<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageDispatcherInterface;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageResilientInterface;
use SlothDevGuy\RabbitMQMessages\Services\FacadeService;
use SlothDevGuy\RabbitMQMessages\Services\MessageDispatcher;
use SlothDevGuy\RabbitMQMessages\Services\MessageResilient;

/**
 * Class RabbitMQMessagesServiceProvider
 * @package SlothDevGuy\RabbitMQMessages
 */
class RabbitMQMessagesServiceProvider extends ServiceProvider
{
    /**
     * Registers the necessary bindings and instances in the Laravel application container.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->app->bind(MessageDispatcherInterface::class, MessageDispatcher::class);
        $this->app->bind(MessageResilientInterface::class, MessageResilient::class);

        $helper = $this->app->make(FacadeService::class);
        $this->app->instance(RabbitMQMessage::ACCESSOR, $helper);

        if($this->app->runningInConsole()){
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }

    /**
     * Configure the publishing of resources.
     *
     * This method is called during the booting of the application.
     * It is responsible for setting up the publishing of resources, such as configuration files.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configurePublishing();
    }

    /**
     * Configure the publishing of RabbitMQ Messages migrations.
     *
     * @return void
     */
    protected function configurePublishing(): void
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../database/migrations/2024_05_20_152036_create_rabbitmq_messages_tables.php' =>
                database_path('migrations/2024_05_20_152036_create_rabbitmq_messages_tables.php'),
        ], 'rabbitmq-messages-migrations');

        $this->publishes([
            __DIR__ . '/../config/rabbitmq-messages.php' => config_path('rabbitmq-messages.php'),
            __DIR__ . '/../config/queue.php' => config_path('queue.php'),
        ], 'rabbitmq-messages-config');
    }
}
