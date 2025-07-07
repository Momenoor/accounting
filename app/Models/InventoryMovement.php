<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'quantity_change', // Positive for additions, negative for deductions
        'movement_type',  // purchase, sale, adjustment, return, etc.
        'reference_id',    // ID of related document (bill, invoice, etc.)
        'reference_type',  // Class of related document
        'notes',
        'unit_cost',       // Cost per unit at time of movement
        'total_cost',      // Total cost (quantity_change * unit_cost)
        'user_id',         // Who initiated the movement
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity_change' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    /**
     * The attributes with default values.
     *
     * @var array
     */
    protected $attributes = [
        'quantity_change' => 0,
        'unit_cost' => 0,
        'total_cost' => 0,
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($movement) {
            // Automatically calculate total cost if not set
            if (is_null($movement->total_cost)) {
                $movement->total_cost = $movement->quantity_change * $movement->unit_cost;
            }

            // Set current user if not specified
            if (is_null($movement->user_id) && auth()->check()) {
                $movement->user_id = auth()->id();
            }

            // Set current time if not specified
            if (is_null($movement->occurred_at)) {
                $movement->occurred_at = now();
            }
        });
    }

    /**
     * Get the product that owns the inventory movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who recorded the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the related reference model.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for movements of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope for movements that increased inventory.
     */
    public function scopeIncreases($query)
    {
        return $query->where('quantity_change', '>', 0);
    }

    /**
     * Scope for movements that decreased inventory.
     */
    public function scopeDecreases($query)
    {
        return $query->where('quantity_change', '<', 0);
    }

    /**
     * Scope for movements within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Get the current stock level after this movement.
     * This would require calculating based on previous movements.
     */
    public function getCurrentStockAttribute()
    {
        return $this->product->inventory_movements()
            ->where('occurred_at', '<=', $this->occurred_at)
            ->sum('quantity_change');
    }
}
