<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use AllowDynamicProperties;
use App\Filament\Resources\BankTransactionResource;
use App\Services\AccountService;
use App\Services\BankTransactionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

#[AllowDynamicProperties]
class EditBankTransaction extends EditRecord
{
    protected static string $resource = BankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $amount = $this->record->amount - $this->record->getPrevious()['amount'];
        app(BankTransactionService::class)->updateBankTransactionJournal($this->record);
        AccountService::updateBankBalance($this->record->bank_account_id, $amount, $this->record->type);

    }

}
