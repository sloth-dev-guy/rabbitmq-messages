<?php

namespace SlothDevGuy\RabbitMQMessages\Exceptions;

use Exception;
use SlothDevGuy\RabbitMQMessages\Interfaces\SkipListenMessageThrowable;

class InvalidRabbitMQMessageException extends Exception  implements SkipListenMessageThrowable
{

}
