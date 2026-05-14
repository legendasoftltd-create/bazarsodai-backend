<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number', 'reference_type', 'reference_id',
        'event_type', 'description', 'status',
        'reversal_of_id', 'created_by', 'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversal_of_id');
    }

    /** Assert debit == credit (for unit tests / pre-save validation) */
    public function isBalanced(): bool
    {
        $debits  = $this->lines->sum('debit');
        $credits = $this->lines->sum('credit');

        return abs($debits - $credits) < 0.001;
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }
}
