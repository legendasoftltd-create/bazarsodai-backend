<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'stock_transfer_id' => 'integer',
        'item_id'           => 'integer',
        'qty_requested'     => 'float',
        'qty_transferred'   => 'float',
        'qty_received'      => 'float',
        'batch_id'          => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}
