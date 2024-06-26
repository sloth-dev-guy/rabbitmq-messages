<?php

namespace SlothDevGuy\RabbitMQMessages\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Models\Enums\DispatchMessageStatusEnum;
use Throwable;

/**
 * Class DispatchEvent
 * @package App\Models
 *
 * @property int id
 * @property string uuid
 * @property string name
 * @property DispatchMessageStatusEnum status
 * @property Collection properties
 * @property Collection payload
 * @property Collection metadata
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class DispatchMessageModel extends Model
{
    use HasTimestamps;

    protected $table = 'dispatch_message';

    protected $guarded = ['id', 'uuid', 'status', 'created_at', 'updated_at'];

    protected $casts = [
        'status' => DispatchMessageStatusEnum::class,
        'properties' => AsCollection::class,
        'payload' => AsCollection::class,
        'metadata' => AsCollection::class,
    ];

    /**
     * @inheritdoc
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('rabbitmq-messages.database_connection');
    }

    /**
     * Sets the dispatched date of the event.
     *
     * @param Carbon|null $dispatchedAt The dispatched date.
     * @return void
     */
    public function setDispatchedAt(Carbon $dispatchedAt = null): void
    {
        $dispatchedAt = $dispatchedAt ?? now();

        $this->properties = $this->properties->merge([
            'timestamp' => $dispatchedAt->getTimestamp(),
        ]);

        $this->payload = $this->payload->merge([
            'fired_at' => $dispatchedAt->toIso8601String(),
        ]);

        $this->status = DispatchMessageStatusEnum::DISPATCHED;
    }

    /**
     * @param Throwable|null $reason
     * @param Carbon|null $failedAt
     * @return void
     */
    public function setFailedAt(Throwable $reason = null, Carbon $failedAt = null): void
    {
        $failedAt = $failedAt ?? now();
        $exceptions = $this->metadata->get('exceptions', []);
        $exceptions[] = [
            'message' => $reason?->getMessage(),
            'failed_at' => $failedAt->toIso8601String(),
        ];
        $this->metadata = $this->metadata->merge([
            'exceptions' => $exceptions,
        ]);
        $this->status  = DispatchMessageStatusEnum::FAILED;
    }
}
