<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'from_store_id'  => 'integer',
        'to_store_id'    => 'integer',
        'module_id'      => 'integer',
        'created_by'     => 'integer',
        'transferred_at' => 'datetime',
        'received_at'    => 'datetime',
    ];

    public function fromStore()
    {
        return $this->belongsTo(\App\Models\Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(\App\Models\Store::class, 'to_store_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public static function generateTransferNumber(): string
    {
        $last = static::latest('id')->value('transfer_number');
        $next = $last ? (int) substr($last, -6) + 1 : 1;
        return 'ST-' . date('Y') . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
