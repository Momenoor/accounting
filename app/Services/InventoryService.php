<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function processBillInventory(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            foreach ($bill->items as $item) {
                if ($item->product_id) {
                    $this->recordPurchaseMovement($item);
                    $this->updateProductStock($item);
                }
            }
        });
    }

    protected function recordPurchaseMovement(BillItem $item): void
    {
        InventoryMovement::create([
            'product_id' => $item->product_id,
            'quantity_change' => $item->quantity,
            'movement_type' => 'purchase',
            'unit_cost' => $item->unit_price,
            'total_cost' => $item->quantity * $item->unit_price,
            'reference_id' => $item->bill_id,
            'reference_type' => Bill::class,
            'notes' => "Purchase from bill #{$item->bill->bill_number}",
        ]);
    }

    protected function updateProductStock(BillItem $item): void
    {
        $product = $item->product;
        $product->increment('quantity', $item->quantity);

        // Update average cost if needed
        $product->last_purchase_cost = $item->unit_price;
        $product->save();
    }

    public function reverseBillInventory(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            foreach ($bill->items as $item) {
                if ($item->product_id) {
                    $this->recordReversalMovement($item);
                    $this->reverseProductStock($item);
                }
            }
        });
    }

    protected function recordReversalMovement(BillItem $item): void
    {
        InventoryMovement::create([
            'product_id' => $item->product_id,
            'quantity_change' => -$item->quantity,
            'movement_type' => 'purchase_reversal',
            'unit_cost' => $item->unit_price,
            'total_cost' => -($item->quantity * $item->unit_price),
            'reference_id' => $item->bill_id,
            'reference_type' => Bill::class,
            'notes' => "Reversal for bill #{$item->bill->bill_number}",
        ]);
    }

    protected function reverseProductStock(BillItem $item): void
    {
        $product = $item->product;
        $product->decrement('quantity', $item->quantity);
        $product->save();
    }
}
