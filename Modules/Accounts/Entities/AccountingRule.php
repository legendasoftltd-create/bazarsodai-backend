<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingRule extends Model
{
    protected $fillable = [
        'event_type', 'lines', 'description', 'is_active',
    ];

    protected $casts = [
        'lines'     => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
