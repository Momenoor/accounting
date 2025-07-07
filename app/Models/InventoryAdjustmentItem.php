<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustmentItem extends Model
{
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'direction',
        'notes',
    ];

    public function adjustment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'adjustment_id');
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
