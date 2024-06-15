<?php

namespace SlothDevGuy\RabbitMQMessages\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SlothDevGuy\RabbitMQMessages\Models\Enums\ListenMessageStatusEnum;

/**
 * Class ListenEvent
 * @package App\Models
 *
 * @property int id
 * @property string uuid
 * @property string name
 * @property ListenMessageStatusEnum status
 * @property Collection properties
 * @property Collection payload
 * @property Collection metadata
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class ListenMessageModel extends Model
{
    use HasTimestamps;

    protected $table = 'listen_message';

    protected $guarded = ['id', 'uuid', 'status', 'created_at', 'updated_at'];

    protected $casts = [
        'status' => ListenMessageStatusEnum::class,
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

        $this->connection = config('rabbitmq-messages.connection');
    }

    public function setAsQueued(Carbon $queuedAt = null): void
    {
        $queuedAt = $queuedAt ?? now();
        $this->status = ListenMessageStatusEnum::QUEUED;
        $this->metadata = $this->metadata->merge([
            'queued_at' => $queuedAt->toIso8601String(),
        ]);
    }

    public function setAsProcessed(Carbon $processedAt = null): void
    {
        $processedAt = $processedAt ?? now();

        $this->status = ListenMessageStatusEnum::PROCESSED;
        $this->metadata = $this->metadata->merge([
            'processed_at' => $processedAt->toIso8601String(),
        ]);
    }

    public function setAsFailed(Carbon $failedAt = null): void
    {
        $failedAt = $failedAt ?? now();

        $this->status = ListenMessageStatusEnum::FAILED;
        $this->metadata = $this->metadata->merge([
            'failed_at' => $failedAt->toIso8601String(),
        ]);
    }

    /**
     * @param string $uuid
     * @return static|null
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public static function findByUuid(string $uuid): static|null
    {
        return static::query()->where('uuid', $uuid)->first();
    }
}
