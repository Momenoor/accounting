<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use App\Filament\Resources\BankTransactionResource;
use App\Models\BankTransaction;
use App\Services\AccountService;
use App\Services\BankTransactionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBankTransaction extends CreateRecord
{
    protected static string $resource = BankTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['description'])) {
            $data['description'] = "Record {$data['type']} bank transaction";
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        app(BankTransactionService::class)->createJournalEntry($this->record);
        AccountService::updateBankBalance($this->record->bank_account_id, $this->record->amount, $this->record->type);
    }

}
