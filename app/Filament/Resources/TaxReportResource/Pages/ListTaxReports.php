<?php

namespace App\Filament\Resources\TaxReportResource\Pages;

use App\Filament\Resources\TaxReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxReports extends ListRecords
{
    protected static string $resource = TaxReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
