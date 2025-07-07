<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'sku', 'description', 'price', 'cost',
        'quantity', 'category_id', 'inventory_account_id',
        'cogs_account_id', 'last_purchase_cost'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function costOfGoodsSoldAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getCurrentStockAttribute()
    {
        return $this->inventoryMovements()->sum('quantity_change');
    }

    public function getAverageCostAttribute(): float|int
    {
        $totalQuantity = $this->inventoryMovements()->increases()->sum('quantity_change');
        $totalCost = $this->inventoryMovements()->increases()->sum('total_cost');

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }
}
