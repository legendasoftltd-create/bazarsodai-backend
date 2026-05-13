<?php

namespace Modules\Inventory\Jobs;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Modules\Inventory\Entities\StockBatch;
use Modules\Inventory\Notifications\ExpiryAlertNotification;

class ExpiryAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $alertDays = config('inventory.expiry_alert_days', 30);

        StockBatch::with(['item.store.vendor'])
            ->available()
            ->expiringSoon($alertDays)
            ->chunkById(100, function ($batches) {
                foreach ($batches as $batch) {
                    $store = $batch->item?->store;
                    if (!$store) continue;

                    $daysLeft = (int) now()->diffInDays($batch->expires_at, false);
                    if ($daysLeft < 0) continue;

                    $emails = array_filter([
                        optional($store->vendor)->email,
                        config('inventory.alert_email'),
                    ]);

                    if (empty($emails)) continue;

                    Notification::route('mail', $emails)
                        ->notify(new ExpiryAlertNotification($batch, $daysLeft));
                }
            });
    }
}
