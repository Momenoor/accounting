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

    protected static function booted()
    {
        static::creating(function ($billItem) {
            $subtotal = $billItem->quantity * $billItem->unit_price;
            $taxRate = $billItem->taxRate;

            $billItem->total = $taxRate ?
                $subtotal + ($subtotal * $taxRate->rate / 100) :
                $subtotal;
            $billItem->account_id = $billItem->product->inventory_account_id;
        });

        static::updating(function ($billItem) {
            $subtotal = $billItem->quantity * $billItem->unit_price;
            $taxRate = $billItem->taxRate;

            $billItem->total = $taxRate ?
                $subtotal + ($subtotal * $taxRate->rate / 100) :
                $subtotal;
            $billItem->account_id = $billItem->product->inventory_account_id;
        });
        // When a bill item is created (purchase)
        static::created(function (BillItem $billItem) {
            if ($billItem->product_id) {
                // Create inventory movement
                InventoryMovement::create([
                    'product_id' => $billItem->product_id,
                    'quantity_change' => $billItem->quantity,
                    'movement_type' => 'purchase',
                    'unit_cost' => $billItem->unit_price,
                    'total_cost' => $billItem->quantity * $billItem->unit_price,
                    'reference_id' => $billItem->bill_id,
                    'reference_type' => Bill::class,
                    'notes' => "Purchase from bill #{$billItem->bill->bill_number}",
                ]);

                // Update product quantity
                $billItem->product->increment('quantity', $billItem->quantity);
                $billItem->product->update(['last_purchase_cost' => $billItem->unit_price]);
            }
        });

        // When a bill item is updated
        static::updated(function (BillItem $billItem) {
            if ($billItem->product_id && $billItem->isDirty('quantity')) {
                $quantityDifference = $billItem->quantity - $billItem->getOriginal('quantity');

                InventoryMovement::create([
                    'product_id' => $billItem->product_id,
                    'quantity_change' => $quantityDifference,
                    'movement_type' => 'purchase_adjustment',
                    'unit_cost' => $billItem->unit_price,
                    'total_cost' => $quantityDifference * $billItem->unit_price,
                    'reference_id' => $billItem->bill_id,
                    'reference_type' => Bill::class,
                    'notes' => "Quantity adjustment for bill #{$billItem->bill->bill_number}",
                ]);

                $billItem->product->increment('quantity', $quantityDifference);
            }
        });

        // When a bill item is deleted
        static::deleting(function (BillItem $billItem) {
            if ($billItem->product_id) {
                InventoryMovement::create([
                    'product_id' => $billItem->product_id,
                    'quantity_change' => -$billItem->quantity,
                    'movement_type' => 'purchase_reversal',
                    'unit_cost' => $billItem->unit_price,
                    'total_cost' => -($billItem->quantity * $billItem->unit_price),
                    'reference_id' => $billItem->bill_id,
                    'reference_type' => Bill::class,
                    'notes' => "Reversal for deleted bill item from bill #{$billItem->bill->bill_number}",
                ]);

                $billItem->product->decrement('quantity', $billItem->quantity);
            }
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
