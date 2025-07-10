<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit', 'memo'
    ];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
    ];

    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
