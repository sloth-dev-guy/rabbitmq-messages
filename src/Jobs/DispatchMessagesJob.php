<?php

namespace SlothDevGuy\RabbitMQMessages\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use SlothDevGuy\RabbitMQMessages\Models\DispatchMessageModel;
use SlothDevGuy\RabbitMQMessages\RabbitMQQueue;
use Throwable;

/**
 * Class DispatchEvents
 * @package SlothDevGuy\RabbitMQMessages\Jobs
 */
class DispatchMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected DispatchMessageModel $dispatchEvent,
    )
    {
        $this->queue = $this->dispatchEvent->metadata->get('queue', 'rabbitmq-messages.dispatch-queue');
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            DB::connection($this->dispatchEvent->getConnectionName())->transaction(function (){
                $connection = $this->dispatchEvent->metadata->get('connection', 'rabbitmq');
                /** @var RabbitMQQueue $rabbitmq */
                $rabbitmq = Queue::connection($connection);
                $rabbitmq->dispatchMessage($this->dispatchEvent);

                logger()->info('event-published', $this->dispatchEvent->properties->toArray());

                $this->dispatchEvent->setDispatchedAt();
                $this->dispatchEvent->save();
            });
        }
        catch (Throwable $e) {
            logger()->error("event-publish failed: {$e->getMessage()}", $this->dispatchEvent->properties->toArray());

            $this->dispatchEvent->setFailedAt();
            $this->dispatchEvent->save();

            throw $e;
        }
    }
}
