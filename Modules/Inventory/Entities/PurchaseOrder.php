<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'supplier_id'  => 'integer',
        'store_id'     => 'integer',
        'module_id'    => 'integer',
        'total_qty'    => 'float',
        'sub_total'    => 'float',
        'tax_amount'   => 'float',
        'discount'     => 'float',
        'total_cost'   => 'float',
        'ordered_at'   => 'datetime',
        'expected_at'  => 'date',
        'received_at'  => 'datetime',
        'created_by'   => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    public static function generatePoNumber(): string
    {
        $last = static::latest('id')->value('po_number');
        $next = $last ? (int) substr($last, -6) + 1 : 1;
        return 'PO-' . date('Y') . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
