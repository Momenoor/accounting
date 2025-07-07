<?php

namespace App\Filament\Resources\InventoryMovementResource\Widgets;

use App\Models\InventoryMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MovementImpactWidget extends BaseWidget
{
    public ?InventoryMovement $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Current Stock', function () {
                $product = $this->record->product;
                return $product->quantity + $this->record->quantity_change;
            }),

            Stat::make('Cost Impact', function () {
                return '$' . number_format(abs($this->record->total_cost), 2);
            })
                ->description($this->record->quantity_change > 0 ? 'Added to inventory' : 'Removed from inventory'),

            Stat::make('Average Cost Before', function () {
                $product = $this->record->product;
                return '$' . number_format($product->average_cost, 2);
            }),
        ];
    }
}
