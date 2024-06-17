<?php

namespace SlothDevGuy\RabbitMQMessages\Exceptions;

use Exception;
use SlothDevGuy\RabbitMQMessages\Interfaces\SkipListenMessageThrowable;

class MessageAlreadyRegisterException extends Exception implements SkipListenMessageThrowable
{

}
