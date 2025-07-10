<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id', 'product_id', 'description', 'quantity',
        'unit_price', 'tax_rate', 'tax_rate_id', 'total', 'account_id'
    ];

    protected $with = ['product', 'account'];
    protected $casts = [
        'total' => 'float',
        'tax_rate' => 'float',
        'unit_price' => 'float',
        'quantity' => 'int',
    ];

    protected static function booted(): void
    {
        static::saving(function ($billItem) {
            $subtotal = $billItem->quantity * $billItem->unit_price;
            $taxRate = $billItem->taxRate;

            $billItem->total = $taxRate ?
                $subtotal + ($subtotal * $taxRate->rate / 100) :
                $subtotal;
            $billItem->account_id = $billItem->product->inventory_account_id;
        });
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryMovements(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
