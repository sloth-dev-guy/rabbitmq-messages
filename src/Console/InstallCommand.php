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
        if($this->confirm('Warning! The following action may override files in your folder, do you wish to continue?')){
            $connection = $this->choice('What connections do you want to use?', ['all', 'fanout', 'topic'], 'all');
            $this->call('vendor:publish', ['--tag' => 'rabbitmq-messages-config', '--force' => true]);
            $this->call('vendor:publish', ['--tag' => 'rabbitmq-messages-bin', '--force' => true]);
            $this->cleanUpUnwantedConnections($connection);
            $this->suggestEnvironments($connection);
        }
    }

    /**
     * Removes unwanted queue connection configuration lines from the `queue.php` file.
     *
     * This method modifies the `queue.php` configuration file by removing lines
     * corresponding to the specified queue connection type. If the connection type
     * is 'all', no changes are made.
     *
     * @param string $connection
     */
    protected function cleanUpUnwantedConnections(string $connection): void
    {
        if($connection === 'all'){
            return;
        }
        //remove unwanted lines from queue.php
        $queueConfigRanges = [
            'fanout' => [125, 170],
            'topic' => [78, 124],
        ];
        $configFile = config_path('queue.php');
        $range = $queueConfigRanges[$connection];
        $lines = file($configFile);
        $offset = $range[0] - 1;
        $length = ($range[1] - $range[0]) + 1;
        array_splice($lines, $offset, $length);
        file_put_contents($configFile, implode('', $lines));
        //comment unwanted commands from listen-message.sh
        $listenMessageFile = base_path('etc/bin/listen-messages.sh');
        $lines = file($listenMessageFile);
        $bashCommandLines = [
            'fanout' => [25, 28],
            'topic' => [24, 27],
        ];
        $linesToComments = $bashCommandLines[$connection];
        foreach ($linesToComments as $line){
            $lines[$line - 1] = '#'.$lines[$line - 1];
        }
        file_put_contents($listenMessageFile, implode('', $lines));
    }

    protected function suggestEnvironments(string $connection): void
    {
        $path =  __DIR__ . '/../../.env.example';
        //the file functions returns and array so the first line has index 0
        $fromLine = 18;
        $envLines = [
            'fanout' => [32, 37],
            'topic' => [26, 31],
        ];
        $lines = file($path);
        $linesToRemove = data_get($envLines, $connection, []);
        $suggestedLines = array_filter($lines,
            fn($line) =>
                $line >= $fromLine
                && (
                    empty($linesToRemove)
                    || !($line >= $linesToRemove[0] && $line <= $linesToRemove[1])
                ),
            ARRAY_FILTER_USE_KEY
        );
        $suggestion = PHP_EOL . implode('', $suggestedLines) . PHP_EOL;
        $this->info('Suggested environments to add to your .env and .env.example file: ' . $suggestion);
    }
}
