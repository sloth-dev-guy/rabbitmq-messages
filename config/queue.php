<?php

use PhpAmqpLib\Exchange\AMQPExchangeType;
use SlothDevGuy\RabbitMQMessages\RabbitMQJob;
use SlothDevGuy\RabbitMQMessages\RabbitMQQueue;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => true,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'after_commit' => true,
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USERNAME', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'queue' => [
                    'exchange' => env('RABBITMQ_EXCHANGE', 'amq.fanout'),
                    'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', AMQPExchangeType::FANOUT),
                    'exchange_routing_key' => config('RABBITMQ_ROUTING_KEY', ''),
                    'job' => RabbitMQJob::class,
//                    'reroute_failed' => true,
//                    'failed_exchange' => 'failed-exchange',
//                    'failed_routing_key' => 'application-x.%s',
                ],
            ],
            //message customizations
            'worker' => RabbitMQQueue::class,
            'app_id' => env('RABBITMQ_APP_ID'),
            //retries configuration
            'retry_queue' => env('RABBITMQ_RETRY_QUEUE'),
            'retry_queue_delay' => env('RABBITMQ_RETRY_QUEUE_DELAY', 3000),
            'retry_exchange' => env('RABBITMQ_RETRY_EXCHANGE', 'amq.direct'),
            'retry_exchange_type' => env('RABBITMQ_RETRY_EXCHANGE_TYPE', AMQPExchangeType::DIRECT),
            'retry_exchange_routing_key' => env('RABBITMQ_RETRY_ROUTING_KEY'),
            //dead letter configuration
            'dead_letter_queue' => env('RABBITMQ_DEAD_LETTER_QUEUE'),
            'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'amq.direct'),
            'dead_letter_exchange_type' => env('RABBITMQ_DEAD_LETTER_EXCHANGE_TYPE', AMQPExchangeType::DIRECT),
            'dead_letter_exchange_routing_key' => env('RABBITMQ_DEAD_LETTER_ROUTING_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
