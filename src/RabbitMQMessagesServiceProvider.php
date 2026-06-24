<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageDispatcherInterface;
use SlothDevGuy\RabbitMQMessages\Interfaces\MessageResilientInterface;
use SlothDevGuy\RabbitMQMessages\Services\FacadeService;
use SlothDevGuy\RabbitMQMessages\Services\MessageDispatcher;
use SlothDevGuy\RabbitMQMessages\Services\MessageResilient;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\ConsumeCommand;

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
            //temporary fix until this issue is resolved: https://github.com/vyuldashev/laravel-queue-rabbitmq/issues/668
            $this->app->singleton(ConsumeCommand::class, static function ($app) {
                return new class($app['rabbitmq.consumer'], $app['cache.store']) extends ConsumeCommand {
                    protected $signature = 'rabbitmq:consume
                            {connection? : The name of the queue connection to work}
                            {--name=default : The name of the consumer}
                            {--queue= : The name of the queue to work. Please notice that there is no support for multiple queues}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--stop-when-empty-for=0 : Stop when no jobs have been processed for the given number of seconds}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--rest=0 : Number of seconds to rest between jobs}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--json : Output the queue worker information as JSON}

                            {--max-priority=}
                            {--consumer-tag}
                            {--prefetch-size=0}
                            {--prefetch-count=1000}
                           ';
                };
            });

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

        $this->publishes([
            __DIR__ . '/../stubs/bin/dispatch-messages.stub' => base_path('etc/bin/dispatch-messages.sh'),
            __DIR__ . '/../stubs/bin/listen-messages.stub' => base_path('etc/bin/listen-messages.sh'),
        ], 'rabbitmq-messages-bin');
    }
}
