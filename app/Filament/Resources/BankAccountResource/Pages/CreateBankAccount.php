<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;


    protected function mutateFormDataBeforeCreate($data): array
    {
        $data['current_balance'] = $data['opening_balance'];
        return $data;
    }
}
