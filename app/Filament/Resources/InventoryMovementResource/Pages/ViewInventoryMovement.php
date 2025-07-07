<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\View;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryMovement extends ViewRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryMovementResource\Widgets\MovementImpactWidget::class,
        ];
    }
}
