<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustmentItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'adjustment_id' => 'integer',
        'item_id'       => 'integer',
        'system_qty'    => 'float',
        'physical_qty'  => 'float',
        'difference'    => 'float',
        'batch_id'      => 'integer',
    ];

    public function adjustment()
    {
        return $this->belongsTo(InventoryAdjustment::class);
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
