<?php

namespace App\Services;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransactionService
{
    public function createJournalEntry($bankTransaction): void
    {
        $journalEntry = $bankTransaction->journalEntry()->create([
            'entry_date' => $bankTransaction->transaction_date,
            'reference_number' => $bankTransaction->reference,
            'description' => "Journal entry for Bank transaction #{$bankTransaction->reference}",
            'fiscal_year_id' => FiscalYear::getCurrentID(),
        ]);

        // Debit bank transaction accounts for each item
        $this->createJournalEntryTransactions($bankTransaction, $journalEntry);
    }

    protected function createJournalEntryTransactions($bankTransaction, $journalEntry): void
    {
        $debit = $bankTransaction->type !== 'deposit' ? 0 : $bankTransaction->amount;
        $credit = $bankTransaction->type !== 'deposit' ? $bankTransaction->amount : 0;
        $bankTransactionJournalEntryTransaction[] = [
            'account_id' => $bankTransaction->bankAccount->account_id,
            'memo' => $bankTransaction->description,
            'debit' => $debit,
            'credit' => $credit,
        ];
        $bankTransaction->bankAccount->current_balance += $debit - $credit;
        app(AccountService::class)->applyTransaction($bankTransaction->bankAccount->account_id, $debit, $credit);

        foreach ($bankTransaction->transactionItems as $transactionItem) {
            $debit = $bankTransaction->type !== 'deposit' ? $transactionItem->total : 0;
            $credit = $bankTransaction->type !== 'deposit' ? 0 : $transactionItem->total;
            $bankTransactionJournalEntryTransaction[] = [
                'account_id' => $transactionItem->account_id,
                'memo' => $transactionItem->description ?? $bankTransaction->description,
                'debit' => $debit,
                'credit' => $credit,
            ];
            app(AccountService::class)->applyTransaction($transactionItem->account_id, $debit, $credit);
        }

        $journalEntry->transactions()->createMany($bankTransactionJournalEntryTransaction);
    }

    public function updateBankTransactionJournal($bankTransaction): void
    {
        $journalEntry = $bankTransaction->journalEntry;
        foreach ($journalEntry->transactions as $transaction) {
            app(AccountService::class)->applyTransaction($transaction->account_id, $transaction->credit, $transaction->debit);
            $transaction->delete();
        }
        $this->createJournalEntryTransactions($bankTransaction, $journalEntry);
    }
}
