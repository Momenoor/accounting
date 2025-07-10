<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'name', 'bank_name', 'account_number', 'currency',
        'opening_balance', 'current_balance', 'account_id', 'is_active'
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }
}
