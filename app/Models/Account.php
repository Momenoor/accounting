<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Kalnoy\Nestedset\NodeTrait;

class Account extends Model
{
    use NodeTrait;

    protected $fillable = [
        'name', 'code', 'type', 'opening_balance', '_lft', '_rgt',
        'current_balance', 'parent_id', 'is_system_account', 'description'
    ];

    protected $casts = [
        'is_system_account' => 'boolean',
        'opening_balance' => 'float',
        'current_balance' => 'float',
    ];

    protected static function booted(): void
    {
        static::deleting(function ($account) {
            if (
                $account->bankAccounts()->exists() ||
                $account->transactions()->exists()
            ) {
                Notification::make()
                    ->title("You can't delete this account")
                    ->body("It has related bank accounts or transactions.")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'account' => "Deletion blocked: related records exist.",
                ]);
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeAssets($query)
    {
        return $query->where('type', 'asset');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('type', 'liability');
    }

    public function scopeEquity($query)
    {
        return $query->where('type', 'equity');
    }

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function formattedLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => '[' . $this->code . '] - ' . $this->name,
        );
    }

    public function debitOpeningBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->opening_balance >= 0 ? $this->opening_balance : 0,
        );
    }

    public function creditOpeningBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->opening_balance < 0 ? $this->opening_balance * -1 : 0,
        );
    }

    public function debitCurrentBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_balance >= 0 ? $this->current_balance : 0,
        );
    }

    public function creditCurrentBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_balance < 0 ? $this->current_balance * -1 : 0,
        );
    }
}
