<?php

namespace App\Models;

use App\Services\AccountService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_account_id', 'transaction_date', 'description',
        'reference', 'amount', 'type', 'is_reconciled', 'transaction_id'
    ];

    public static function booted()
    {
        static::deleting(function ($bankTransaction) {
            if ($bankTransaction->transactionable) {
                $related = $bankTransaction->transactionable;

                $related->update([
                    'status' => 'received'
                ]);
            }
            $journalEntry = $bankTransaction->journalEntry;
            $transactions = $journalEntry->transactions;
            foreach ($transactions as $transaction) {
                app(AccountService::class)->applyTransaction($transaction->account_id, $transaction->credit, $transaction->debit);
                $transaction->delete();

            }
            $journalEntry->delete();
            $type = $bankTransaction->type !== 'deposit' ? 'deposit' : 'withdrawal';
            AccountService::updateBankBalance($bankTransaction->bank_account_id, $bankTransaction->amount, $type);
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'entryable');
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactionItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankTransactionItem::class);
    }


}
