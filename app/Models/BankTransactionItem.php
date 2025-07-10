<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransactionItem extends Model
{
    protected $fillable = [
        'account_id',
        'total',
        'bank_transaction_id',
        'description',
    ];

    protected $casts = [
        'total' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }
}
