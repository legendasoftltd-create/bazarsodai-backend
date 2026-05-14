<?php

namespace Modules\Accounts\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Accounts\Events\AccountingEventOccurred;
use Modules\Accounts\Services\AccountingService;
use Modules\Accounts\Exceptions\UnbalancedJournalException;
use Illuminate\Support\Facades\Log;

class PostJournalEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'accounting';
    public int    $tries = 3;
    public int    $backoff = 10;

    public function __construct(protected AccountingService $accounting) {}

    public function handle(AccountingEventOccurred $event): void
    {
        $context = array_filter([
            'reference_type' => $event->referenceType ?: null,
            'reference_id'   => $event->referenceId   ?: null,
            'description'    => $event->description   ?: null,
            'store_id'        => $event->data['store_id']        ?? null,
            'delivery_man_id' => $event->data['delivery_man_id'] ?? null,
            'order_id'        => $event->data['order_id']        ?? null,
            'user_id'         => $event->data['user_id']         ?? null,
        ], fn($v) => $v !== null);

        $this->accounting->post($event->eventType, $event->data, $context);
    }

    public function failed(AccountingEventOccurred $event, \Throwable $e): void
    {
        Log::error('PostJournalEntry failed', [
            'event_type'     => $event->eventType,
            'reference_type' => $event->referenceType,
            'reference_id'   => $event->referenceId,
            'error'          => $e->getMessage(),
        ]);
    }
}
