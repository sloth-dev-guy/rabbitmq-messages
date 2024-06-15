<?php

namespace SlothDevGuy\RabbitMQMessages\Models\Enums;

enum DispatchMessageStatusEnum: string
{
    case CREATED = 'created';

    case DISPATCHED = 'dispatched';

    case FAILED = 'failed';
}
