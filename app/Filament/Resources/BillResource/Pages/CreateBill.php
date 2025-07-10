<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\BillItem;
use App\Services\BillService;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function afterCreate(): void
    {
        $inventoryService = app(InventoryService::class);
        $inventoryService->processBillInventory($this->record);

        $billService = app(BillService::class);
        $billService->createJournalEntryForBill($this->record);
    }
}
