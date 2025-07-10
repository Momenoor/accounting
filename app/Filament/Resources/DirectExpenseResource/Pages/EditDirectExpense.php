<?php

namespace App\Filament\Resources\DirectExpenseResource\Pages;

use App\Filament\Resources\DirectExpenseResource;
use App\Services\BillService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDirectExpense extends EditRecord
{
    protected static string $resource = DirectExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('bankTransactions');

        if ($transaction = $this->record->bankTransactions->first()) {
            $data['bank_account_id'] = $transaction->bank_account_id;
            $data['transaction_date'] = $transaction->transaction_date;
            $data['reference'] = $transaction->reference;
            $data['description'] = $transaction->description;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(BillService::class)->updateJournalEntryForBill($this->record);

        $transactions = $this->record->bankTransactions;

        if ($transactions) {
            foreach ($transactions as $transaction) {
                $paymentData = $transaction->toArray();
                $transaction->delete();
                $paymentData['amount'] = $this->record->total_amount;
                $paymentData['transactionable'] = $this->record;
                app(PaymentService::class)->recordPayment($paymentData);
            }
        }
    }
}
