<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectExpense extends Model
{
    protected $fillable = [
        'bill_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_rate_id',
        'total',
        'account_id',
    ];

    protected static function booted(): void
    {
        static::saving(function ($directExpense) {
            $subtotal = $directExpense->quantity * $directExpense->unit_price;
            $taxRate = $directExpense->taxRate;

            $directExpense->total = $taxRate ?
                $subtotal + ($subtotal * $taxRate->rate / 100) :
                $subtotal;
        });
    }
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }


}
