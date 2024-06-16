<?php

use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

return [
    'database_connection' => env('RABBITMQ_MESSAGES_DB_CONNECTION', env('DB_CONNECTION', 'sqlite')),

    'models' => [
        'dispatch_message' => DispatchMessageModel::class,
        'listen_message' => ListenMessageModel::class,
    ]
];
