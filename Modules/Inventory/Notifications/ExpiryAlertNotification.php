<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Inventory\Entities\StockBatch;

class ExpiryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly StockBatch $batch,
        public readonly int $daysUntilExpiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item  = $this->batch->item;
        $store = $item?->store;

        return (new MailMessage)
            ->subject("[Expiry Alert] {$item?->name} expires in {$this->daysUntilExpiry} days")
            ->greeting('Expiry Alert')
            ->line("A stock batch is approaching its expiry date.")
            ->line("**Item:** " . ($item?->name ?? "Item #{$this->batch->item_id}"))
            ->line("**Store:** " . ($store?->name ?? "Store #{$this->batch->store_id}"))
            ->line("**Batch #:** " . ($this->batch->batch_number ?? $this->batch->id))
            ->line("**Qty Remaining:** {$this->batch->qty_remaining}")
            ->line("**Expires:** " . $this->batch->expires_at?->format('d M Y') . " ({$this->daysUntilExpiry} days)")
            ->action('View Expiring Stock', url('/vendor/inventory/reports/expiring'))
            ->line('Please take action to use or return this stock before it expires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'batch_id'         => $this->batch->id,
            'item_id'          => $this->batch->item_id,
            'store_id'         => $this->batch->store_id,
            'qty_remaining'    => $this->batch->qty_remaining,
            'expires_at'       => $this->batch->expires_at?->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry,
        ];
    }
}
