<?php

namespace App\Filament\Resources\DirectExpenseResource\Pages;

use App\Filament\Resources\DirectExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDirectExpenses extends ListRecords
{
    protected static string $resource = DirectExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()
            ->whereHas('expenses'); // Only bills with expenses
    }
}
