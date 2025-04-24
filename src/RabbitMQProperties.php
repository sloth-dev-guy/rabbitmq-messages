<?php

namespace SlothDevGuy\RabbitMQMessages;

use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use SlothDevGuy\RabbitMQMessages\Models\ListenMessageModel;

class RabbitMQProperties
{
    /**
     * @param AMQPTable $table
     * @return array
     */
    public static function castAMQPTable(AMQPTable $table): array
    {
        return array_filter($table->getNativeData(), fn($key) => !Str::startsWith($key, 'x-'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param AMQPMessage $message
     * @return array
     */
    public static function fromAMQPMessage(AMQPMessage $message): array
    {
        $properties = $message->get_properties();
        if(isset($properties['application_headers'])){
            $properties['application_headers'] = static::castAMQPTable($properties['application_headers']);
        }

        return $properties;
    }

    /**
     * @param ListenMessageModel $message
     * @return array
     */
    public static function fromListenedMessage(ListenMessageModel $message): array
    {
        $properties = $message->properties->toArray();
        if(isset($properties['application_headers'])){
            $properties['application_headers'] = static::castAsAMQPTable($properties['application_headers']);
        }

        return $properties;
    }

    /**
     * @param array $headers
     * @return AMQPTable
     */
    public static function castAsAMQPTable(array $headers): AMQPTable
    {
        $table = new AMQPTable();
        foreach ($headers as $key => $value) {
            $table->set($key, is_array($value)? static::castAsAMQPTable($value) : $value);
        }
        return $table;
    }
}
