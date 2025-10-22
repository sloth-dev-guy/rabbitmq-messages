<?php

namespace SlothDevGuy\RabbitMQMessages\Exceptions;

use Exception;
use SlothDevGuy\RabbitMQMessages\Interfaces\NonRetriableMessageThrowable;

class NonRetriableMessageException extends Exception implements NonRetriableMessageThrowable
{

}
