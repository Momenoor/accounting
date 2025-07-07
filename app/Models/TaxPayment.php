<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPayment extends Model
{
    protected $fillable = [
        'tax_report_id', 'bank_account_id', 'payment_reference',
        'payment_date', 'amount', 'payment_method', 'notes',
        'journal_entry_id', 'created_by'
    ];

    public function taxReport(): BelongsTo
    {
        return $this->belongsTo(TaxReport::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
