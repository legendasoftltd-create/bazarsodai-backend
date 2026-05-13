<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'store_id'    => 'integer',
        'module_id'   => 'integer',
        'approved_by' => 'integer',
        'created_by'  => 'integer',
        'approved_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function adjustmentItems()
    {
        return $this->hasMany(InventoryAdjustmentItem::class, 'adjustment_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\AdminAndVendorUser::class, 'approved_by');
    }

    public static function generateAdjustmentNumber(): string
    {
        $last = static::latest('id')->value('adjustment_number');
        $next = $last ? (int) substr($last, -6) + 1 : 1;
        return 'ADJ-' . date('Y') . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
