<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'item_id'          => 'integer',
        'store_id'         => 'integer',
        'qty_initial'      => 'float',
        'qty_remaining'    => 'float',
        'unit_cost'        => 'float',
        'manufactured_at'  => 'date',
        'expires_at'       => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('qty_remaining', '>', 0);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<', now());
    }
}
