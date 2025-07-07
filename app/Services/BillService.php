<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;

class BillService
{
    public function createJournalEntryForBill(Bill $bill): JournalEntry
    {
        $journalEntry = $bill->journalEntry()->create([
            'entry_date' => $bill->issue_date,
            'reference_number' => $bill->bill_number,
            'description' => "Journal entry for bill #{$bill->bill_number}",
            'fiscal_year_id' => FiscalYear::getCurrentID(),
        ]);

        // Debit expense accounts for each item
        foreach ($bill->items as $item) {

            $journalEntry->transactions()->create([
                'account_id' => $item->product->inventory_account_id,
                'debit' => $item->total,
                'description' => $item->description,
            ]);
        }

        // Credit accounts payable (total bill amount)
        $journalEntry->transactions()->create([
            'account_id' => $this->getAccountsPayableAccountId(),
            'credit' => $bill->total_amount,
            'description' => 'Accounts Payable for bill',
        ]);

        return $journalEntry;
    }

    protected function getAccountsPayableAccountId()
    {
        // Cache this if called frequently
        return Account::where('code', '2100')->first()->id;
    }
}
