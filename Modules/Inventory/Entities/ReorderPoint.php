<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class ReorderPoint extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'item_id'               => 'integer',
        'store_id'              => 'integer',
        'module_id'             => 'integer',
        'reorder_at'            => 'float',
        'reorder_qty'           => 'float',
        'preferred_supplier_id' => 'integer',
        'auto_notify'           => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function preferredSupplier()
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }
}
