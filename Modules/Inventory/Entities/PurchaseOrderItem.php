<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'item_id'           => 'integer',
        'qty_ordered'       => 'float',
        'qty_received'      => 'float',
        'qty_returned'      => 'float',
        'unit_cost'         => 'float',
        'total_cost'        => 'float',
        'batch_id'          => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
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
