<?php

namespace App\Filament\Resources\DirectExpenseResource\Pages;

use App\Filament\Resources\DirectExpenseResource;
use App\Models\Bill;
use App\Services\BillService;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDirectExpense extends CreateRecord
{
    protected static string $resource = DirectExpenseResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Bill Details
        $data['issue_date'] = $data['transaction_date'];
        $data['due_date'] = $data['transaction_date'];
        $data['status'] = 'paid';

        return $data;
    }

    protected function afterCreate(): void
    {

        app(BillService::class)->createJournalEntryForBill($this->record);
        // 2. Create the payment (BankTransaction)
        $paymentData = [
            'type'                => 'outgoing',
            'amount'              => $this->form->getState()['total_amount'],
            'reference'           => $this->form->getState()['reference'],
            'transaction_date'    => $this->form->getState()['transaction_date'],
            'bank_account_id'     => $this->form->getState()['bank_account_id'],
            'description'         => $this->form->getState()['description'],
            'transactionable'     => $this->record,
        ];

        app(PaymentService::class)->recordPayment($paymentData);;
    }
}
