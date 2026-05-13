<?php

namespace Modules\Inventory\Notifications;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Inventory\Entities\ReorderPoint;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Item $item,
        public readonly Store $store,
        public readonly ReorderPoint $reorderPoint,
        public readonly float $currentStock
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reorderUrl = url('/vendor/inventory/reorder-points');

        return (new MailMessage)
            ->subject("[Low Stock] {$this->item->name} — {$this->store->name}")
            ->greeting("Low Stock Alert")
            ->line("**{$this->item->name}** in store **{$this->store->name}** has fallen below its reorder point.")
            ->line("Current Stock: **{$this->currentStock}**")
            ->line("Reorder At: **{$this->reorderPoint->reorder_at}**")
            ->line("Suggested Reorder Qty: **{$this->reorderPoint->reorder_qty}**")
            ->action('Create Purchase Order', url('/vendor/inventory/purchases/create'))
            ->line('Please restock soon to avoid running out.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'item_id'      => $this->item->id,
            'item_name'    => $this->item->name,
            'store_id'     => $this->store->id,
            'store_name'   => $this->store->name,
            'current_stock' => $this->currentStock,
            'reorder_at'   => $this->reorderPoint->reorder_at,
        ];
    }
}
