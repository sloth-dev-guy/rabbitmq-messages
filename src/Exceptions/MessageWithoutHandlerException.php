<?php

namespace SlothDevGuy\RabbitMQMessages\Exceptions;

use Exception;
use SlothDevGuy\RabbitMQMessages\Interfaces\SkipListenMessageThrowable;

class MessageWithoutHandlerException extends Exception implements SkipListenMessageThrowable
{

}
