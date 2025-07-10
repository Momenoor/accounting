<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\FiscalYear;

class PaymentService
{
    public function recordPayment(array $paymentData): BankTransaction
    {
        // 2. Record bank transaction
        $bankTransaction = $this->recordBankTransaction($paymentData);
        $bankTransaction->transactionable()->associate($paymentData['transactionable']);

        // 3. Update bank account balance
        AccountService::updateBankBalance($bankTransaction->bank_account_id, $bankTransaction->amount, $bankTransaction->type);

        // 4. Create journal entries
        $this->createJournalEntries($bankTransaction);

        return $bankTransaction;

    }

    protected function recordBankTransaction($payment): BankTransaction
    {
        return $payment['transactionable']->bankTransactions()->create([
            'bank_account_id' => $payment['bank_account_id'],
            'amount' => $payment['amount'],
            'type' => $payment['type'] === 'incoming' ? 'deposit' : 'withdrawal',
            'transaction_date' => $payment['transaction_date'],
            'reference' => $payment['reference'],
            'description' => $payment['description'],
        ]);
    }

    protected function createJournalEntries(BankTransaction $bankTransaction): void
    {
        if ($bankTransaction->payment_type === 'incoming') {
            // Incoming payment (customer paying us)
            $journalEntry = $bankTransaction->journalEntry()->create([
                'entry_date' => $bankTransaction->transaction_date,
                'reference_number' => $bankTransaction->reference,
                'description' => "Journal entry for Bank Transaction #{$bankTransaction->reference}",
                'fiscal_year_id' => FiscalYear::getCurrentID(),
            ]);
            $transactions = [
                [
                    'account_id' => $bankTransaction->bankAccount->account_id, // Vendor's payable account
                    'debit' => $bankTransaction->amount,
                    'credit' => 0,
                    'memo' => 'Bank Deposit (Collection)'
                ],
                [
                    'account_id' => $bankTransaction->transactionable->account_id,
                    'debit' => 0,
                    'credit' => $bankTransaction->amount,
                    'memo' => 'Customer Collection'
                ]
            ];
            $journalEntry->transactions()->createMany($transactions);
            foreach ($transactions as $transaction) {
                app(AccountService::class)->applyTransaction($transaction['account_id'], $transaction['debit'], $transaction['credit']);
            }
        } else {
            // Outgoing payment (us paying vendor)
            $journalEntry = $bankTransaction->journalEntry()->create([
                'entry_date' => $bankTransaction->transaction_date,
                'reference_number' => $bankTransaction->reference,
                'description' => "Journal entry for Bank Transaction #{$bankTransaction->reference}",
                'fiscal_year_id' => FiscalYear::getCurrentID(),
            ]);
            $transactions = [
                [
                    'account_id' => app(BillService::class)->getAccountsPayableAccountId(), // Vendor's payable account
                    'debit' => $bankTransaction->amount,
                    'credit' => 0,
                    'memo' => 'Vendor payment'
                ],
                [
                    'account_id' => $bankTransaction->bankAccount->account_id,
                    'debit' => 0,
                    'credit' => $bankTransaction->amount,
                    'memo' => 'Bank withdrawal (Payment)'
                ]
            ];
            $journalEntry->transactions()->createMany($transactions);

            foreach ($transactions as $transaction) {
                app(AccountService::class)->applyTransaction($transaction['account_id'], $transaction['debit'], $transaction['credit']);
            }
        }
    }
}
