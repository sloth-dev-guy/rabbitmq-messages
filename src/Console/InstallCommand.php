<?php

namespace SlothDevGuy\RabbitMQMessages\Console;

use Illuminate\Console\Command;

/**
 * Class InstallCommand
 * @package SlothDevGuy\RabbitMQMessages\Console
 */
class InstallCommand extends Command
{
    /**
     * @inheritdoc
     * @var string
     */
    protected $name = 'rabbitmq-messages:install';

    /**
     * @inheritdoc
     * @var string
     */
    protected $description = 'Install migrations and configurations for rabbitmq-messages package';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->call('vendor:publish', ['--tag' => 'rabbitmq-messages-migrations']);
        if($this->confirm('Warning! The following action may override files in your configuration folder, do you wish to continue?')){
            $this->call('vendor:publish', ['--tag' => 'rabbitmq-messages-config']);
        }
    }
}
