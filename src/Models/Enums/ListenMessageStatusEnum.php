<?php

namespace SlothDevGuy\RabbitMQMessages\Models\Enums;

enum ListenMessageStatusEnum: string
{
    case QUEUED = 'queued';

    case PROCESSED = 'processed';

    case FAILED = 'failed';
}
