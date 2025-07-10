<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\DirectExpense;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BillService
{
    public function createJournalEntryForBill(Bill $bill)
    {
        $journalEntry = $bill->journalEntry()->create([
            'entry_date' => $bill->issue_date,
            'reference_number' => $bill->bill_number,
            'description' => "Journal entry for bill #{$bill->bill_number}",
            'fiscal_year_id' => FiscalYear::getCurrentID(),
        ]);

        // Debit inventory accounts for each item
        $this->createJournalEntryTransactions($bill, $journalEntry);

    }

    public function getAccountsPayableAccountId()
    {
        return Cache::remember('accounts_payable_id', 3600, function () {
            return Account::where('code', '2100')->first()->id;
        });
    }

    public function updateJournalEntryForBill(Bill $bill): void
    {
        // Find existing journal entry
        $journalEntry = JournalEntry::where([
            'entryable_type' => Bill::class,
            'entryable_id' => $bill->id
        ])->first();

        if ($journalEntry) {

            foreach ($journalEntry->transactions as $transaction) {
                // Reverse debit/credit (swap them to undo the effect)
                app(AccountService::class)->applyTransaction(
                    $transaction->account_id,
                    $transaction->credit, // Now debiting what was credited
                    $transaction->debit     // Now crediting what was debited
                );
            }
            // Delete old items
            $journalEntry->transactions()->delete();

            // Create new items based on current bill
            $this->createJournalEntryTransactions(bill: $bill, journalEntry: $journalEntry);
        } else {
            // Create new if none exists
            $this->createJournalEntryForBill($bill);
        }
    }

    /**
     * @param Bill $bill
     * @param Model $journalEntry
     * @return void
     */
    public function createJournalEntryTransactions(Bill $bill, Model $journalEntry): void
    {
        $totalDebitAmount = 0;

        if ($bill->items->isNotEmpty()) {
            foreach ($bill->items as $item) {
                if ($item->product_id && $item->product->inventory_account_id) {
                    $journalEntry->transactions()->create([
                        'account_id' => $item->product->inventory_account_id,
                        'debit' => $item->total,
                        'credit' => 0,
                        'memo' => $item->description ?: "Inventory - {$item->product->name}",
                    ]);
                    $totalDebitAmount += $item->total;

                    app(AccountService::class)->applyTransaction(
                        $item->product->inventory_account_id,
                        $item->total,
                        0
                    );
                }
            }
        } elseif ($bill->expenses->isNotEmpty()) {

            foreach ($bill->expenses as $expense) {

                $journalEntry->transactions()->create([
                    'account_id' => $expense->account_id,
                    'debit' => $expense->total,
                    'credit' => 0,
                    'memo' => $expense->description ?: "Expenses - {$expense->account->name}",
                ]);
                $totalDebitAmount += $expense->total;

                app(AccountService::class)->applyTransaction(
                    $expense->account_id,
                    $expense->total,
                    0
                );

            }
        }

        // Credit accounts payable (total bill amount)
        $accountsPayableAccountId = $this->getAccountsPayableAccountId();
        $journalEntry->transactions()->create([
            'account_id' => $this->getAccountsPayableAccountId(),
            'debit' => 0,
            'credit' => $totalDebitAmount,
            'memo' => "Accounts Payable for bill #{$bill->bill_number}",
        ]);

        app(AccountService::class)->applyTransaction(
            $accountsPayableAccountId,
            0,
            $totalDebitAmount
        );
    }
}
