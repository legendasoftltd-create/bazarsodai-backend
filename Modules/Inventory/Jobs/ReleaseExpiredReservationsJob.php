<?php

namespace Modules\Inventory\Jobs;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReleaseExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(): void
    {
        Cart::where('stock_reserved', 1)
            ->where('reserved_until', '<', now())
            ->chunkById(200, function ($carts) {
                foreach ($carts as $cart) {
                    $cart->update([
                        'stock_reserved' => 0,
                        'reserved_until' => null,
                    ]);
                }
            });
    }
}
