<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id', 'account_id',
        'debit', 'credit', 'description',
        'store_id', 'delivery_man_id', 'order_id', 'user_id',
        'meta',
    ];

    protected $casts = [
        'debit'  => 'float',
        'credit' => 'float',
        'meta'   => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
