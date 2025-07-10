<?php

namespace App\Filament\Resources\BillResource\Pages;

use AllowDynamicProperties;
use App\Filament\Resources\BillResource;
use App\Services\BillService;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

#[AllowDynamicProperties]
class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        // Store original items before saving
        $this->originalItems = $this->record->items()->get()->keyBy('id');
    }
    protected function afterSave(): void
    {
        $bill = $this->record;
        // For both create and update operations
        $inventoryService = app(InventoryService::class);
        $billService = app(BillService::class);

        $inventoryService->processBillInventoryUpdate($bill,$this->originalItems);
        $billService->updateJournalEntryForBill($bill);

    }
}
