<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'store_id'   => 'integer',
        'module_id'  => 'integer',
        'status'     => 'integer',
        'created_by' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
