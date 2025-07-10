<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function processBillInventory(Bill $bill): void
    {
        foreach ($bill->items as $item) {
            if ($item->product_id) {
                $this->recordPurchaseMovement($item);
                $this->updateProductStock($item);
            }
        }

    }

    public function reverseBillInventory(Bill $bill): void
    {

        foreach ($bill->items as $item) {
            if ($item->product_id) {
                $this->recordReversalMovement($item);
                $this->reverseProductStock($item);
            }
        }

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
        $product->update(['last_purchase_cost' => $item->unit_price]);
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
    }

    // Handle individual item updates (call this from a controller when needed)
    public function handleBillItemUpdate(BillItem $billItem): void
    {
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
    }

    public function processBillInventoryUpdate(Bill $bill, $originalItems): void
    {
        $currentItems = $bill->items->keyBy('id');
        // Calculate differences
        foreach ($originalItems as $itemId => $originalItem) {
            if ($currentItem = $currentItems->get($itemId)) {
                $quantityDiff = $currentItem->quantity - $originalItem->quantity;

                if ($quantityDiff != 0) {
                    $this->adjustInventory(
                        $currentItem->product_id,
                        $quantityDiff,
                        $currentItem->unit_price,
                        $bill,
                        'purchase_adjustment'
                    );
                }
            }
        }

        // 2. Process DELETED items (exist in original but not current)
        foreach ($originalItems as $itemId => $originalItem) {
            if (!$currentItems->has($itemId)) {
                $this->adjustInventory(
                    $originalItem->product_id,
                    -$originalItem->quantity,
                    $originalItem->unit_price,
                    $bill,
                    'purchase_reversal'
                );
            }
        }

        // 3. Process NEWLY ADDED items (exist in current but not original)
        foreach ($currentItems as $itemId => $currentItem) {
            if (!$originalItems->has($itemId)) {
                $this->adjustInventory(
                    $currentItem->product_id,
                    $currentItem->quantity,
                    $currentItem->unit_price,
                    $bill,
                    'purchase_addition'
                );
            }
        }
    }

    private function adjustInventory($productId, $quantity, $unitPrice, $bill, $type)
    {
        $product = Product::find($productId);
        if ($product) {
            $product->increment('quantity', $quantity);

            InventoryMovement::create([
                'product_id' => $productId,
                'quantity_change' => $quantity,
                'movement_type' => $type,
                'unit_cost' => $unitPrice,
                'reference_type' => Bill::class,
                'reference_id' => $bill->id,
            ]);
        }
    }

    public function validateInventoryUpdate(Bill $bill)
    {
        foreach ($bill->items as $item) {
            $product = $item->product;
            $originalQuantity = $item->getOriginal('quantity') ?? 0;
            $difference = $item->quantity - $originalQuantity;

            if ($difference < 0 && abs($difference) > $product->quantity) {
                throw new \Exception(
                    "Cannot reduce quantity by " . abs($difference) .
                    ". Only {$product->quantity} available for {$product->name}"
                );
            }
        }
    }
}
