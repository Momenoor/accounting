<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Cache;

class AccountService
{
    public static function updateBankBalance(int $bankAccountId, float $amount, string $paymentType): void
    {
        $debit = $paymentType === 'deposit' ? $amount : 0;
        $credit = $paymentType !== 'deposit' ? $amount : 0;

        $bankAccount = BankAccount::query()->findOrFail($bankAccountId);
        $bankAccount->current_balance += $debit - $credit;
        $bankAccount->save();
    }

    public function applyTransaction($accountId, $debit, $credit)
    {

        $netChange = $debit - $credit;
        if ($netChange == 0) return;
        $account = Account::query()->lockForUpdate()->findOrFail($accountId);
        $account->current_balance += $netChange;
        $account->save();
        $this->updateParentBalances($account, $netChange);
    }

    public function getBankAccountId(int $bankId): int
    {
        return Cache::remember("bank_account_{$bankId}", 3600, function () use ($bankId) {
            return Account::where('id', $bankId)->firstOrFail()->id;
        });
    }

    public function updateParentBalances($account, float $amount): void
    {
        if ($amount == 0) return;

        $parentIds = $this->getAllParentIds($account);

        $ancestors = Account::whereIn('id', $parentIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($parentIds as $parentId) {
            if ($parent = $ancestors->get($parentId)) {
                $parent->current_balance += $amount;
                $parent->save();
            }
        }
    }

    public function getAllParentIds($account): array
    {
        $parentIds = [];
        $current = $account;

        while ($current->parent_id) {
            $parentIds[] = $current->parent_id;
            $current = Account::find($current->parent_id);
            if (!$current) break;
        }

        return $parentIds;
    }
}
