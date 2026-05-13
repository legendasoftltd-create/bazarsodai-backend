<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'item_id'    => 'integer',
        'store_id'   => 'integer',
        'module_id'  => 'integer',
        'qty_in'     => 'float',
        'qty_out'    => 'float',
        'stock_before' => 'float',
        'stock_after'  => 'float',
        'unit_cost'  => 'float',
        'total_cost' => 'float',
        'batch_id'   => 'integer',
        'created_by' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\AdminAndVendorUser::class, 'created_by');
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
