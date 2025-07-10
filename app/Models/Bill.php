<?php

namespace App\Models;

use App\Services\AccountService;
use App\Services\BillService;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $items
 *
 * @property mixed $expenses
 */
class Bill extends Model
{

    protected $fillable = [
        'bill_number', 'vendor_id', 'issue_date', 'due_date',
        'status', 'total_amount', 'tax_amount', 'notes'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'tax_amount' => 'float',
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    public static function booted(): void
    {
//        static::saved(function ($bill) {
//            // Only process if items were modified
//            if ($bill->wasChanged() || $bill->items->isDirty()) {
//                $inventoryService = app(InventoryService::class);
//                $inventoryService->processBillInventoryUpdate($bill);
//
//                $billService = app(BillService::class);
//                $billService->updateJournalEntryForBill($bill);
//            }
//        });

        static::deleting(function ($bill) {
            if ($bill->bankTransactions()->exists()) {
                Notification::make()->title('Cannot delete bill with associated bank transactions.')->danger()->send();
                return false;
            }
            $inventoryService = app(InventoryService::class);
            $inventoryService->reverseBillInventory($bill);

            // Clean up journal entries
            if ($bill->journalEntry) {
                foreach ($bill->journalEntry->transactions as $transaction) {
                    // Reverse debit/credit (swap them to undo the effect)
                    app(AccountService::class)->applyTransaction(
                        $transaction->account_id,
                        $transaction->credit, // Now debiting what was credited
                        $transaction->debit     // Now crediting what was debited
                    );
                }
                $bill->journalEntry->transactions()->delete();
                $bill->journalEntry->delete();
            }

            // Delete items
            $bill->expenses()->delete();
            $bill->items()->delete();
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

    public function expenses(): HasMany
    {
        return $this->hasMany(DirectExpense::class);
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

    public function bankTransactions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(BankTransaction::class, 'transactionable');
    }

}
