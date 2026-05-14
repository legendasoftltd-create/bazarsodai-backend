<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'normal_balance', 'parent_id',
        'is_system', 'is_active', 'description', 'sort_order',
    ];

    protected $casts = [
        'is_system'  => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    /** Running balance: positive = normal-balance side, negative = contra side */
    public function balance(): float
    {
        $debits  = $this->journalLines()->sum('debit');
        $credits = $this->journalLines()->sum('credit');

        return $this->normal_balance === 'debit'
            ? (float)($debits - $credits)
            : (float)($credits - $debits);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
