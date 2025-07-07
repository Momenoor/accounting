<?php

namespace App\Models;

use App\Services\BillService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $items
 */
class Bill extends Model
{
    protected $fillable = [
        'bill_number', 'vendor_id', 'issue_date', 'due_date',
        'status', 'total_amount', 'tax_amount', 'notes'
    ];

    public static function booted(): void
    {
        static::deleting(function ($bill) {
            $bill->items->delete();
            if ($bill->journalEntry) {
                $bill->journalEntry->transactions()->delete();
                $bill->journalEntry->delete();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    public function inventoryMovements(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function getTotalInventoryCostAttribute()
    {
        return $this->inventoryMovements->sum('total_cost');
    }

}
