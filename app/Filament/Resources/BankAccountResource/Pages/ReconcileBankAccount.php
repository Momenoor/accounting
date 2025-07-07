<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Reconciliation;
use App\Models\Transaction;
use Filament\Resources\Pages\Page;

class ReconcileBankAccount extends Page
{
    protected static string $resource = BankAccountResource::class;

    protected static string $view = 'filament.resources.bank-account-resource.pages.reconcile-bank-account';

    public BankAccount $record;

    public $startDate;
    public $endDate;
    public $endingBalance;
    public array $transactions = [];
    public array $glTransactions = [];

    public function mount(): void
    {
        $this->startDate = now()->subMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->transactions = BankTransaction::where('bank_account_id', $this->record->id)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->get();

        $this->glTransactions = Transaction::where('account_id', $this->record->account_id)
            ->whereHas('journalEntry', function($q) {
                $q->whereBetween('entry_date', [$this->startDate, $this->endDate]);
            })
            ->with('journalEntry')
            ->get();
    }

    public function completeReconciliation(): void
    {
        $reconciliation = Reconciliation::create([
            'bank_account_id' => $this->record->id,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'ending_balance' => $this->endingBalance,
            'is_completed' => true,
        ]);

        BankTransaction::whereIn('id', collect($this->transactions)->pluck('id'))
            ->update(['is_reconciled' => true]);

        $this->notify('success', 'Reconciliation completed successfully');
    }
}
