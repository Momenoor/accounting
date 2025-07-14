<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\AccountService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use InvalidArgumentException;

class OpeningBalanceSetup extends Page implements HasForms
{
    use InteractsWithForms, InteractsWithFormActions, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.opening-balance-setup';
    protected static ?string $navigationGroup = 'Accounting & Financial Setup';

    public ?array $data = [];
    protected $accounts;

    public function mount(): void
    {
        $this->form->fill($this->loadDefaultData());
    }

    protected function getAccounts()
    {
        if (!$this->accounts) {
            $this->accounts = Account::query()
                ->whereIsLeaf()
                ->with('bankAccounts')
                ->get()
                ->keyBy('id');
        }
        return $this->accounts;
    }

    public function loadDefaultData(): array
    {
        $data = [
            'data' => [
                'total_debit' => 0,
                'total_credit' => 0,
                'total_balance' => 0,
                'account' => []
            ]
        ];

        foreach ($this->getAccounts() as $account) {
            $openingBalance = (float)$account->opening_balance;
            $data['data']['account'][$account->id] = [
                'debit' => $openingBalance > 0 ? $openingBalance : 0,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                'opening_balance' => $openingBalance
            ];
        }

        $this->calculateTotals();
        return $data;
    }

    public function getFormSchema(): array
    {
        return [
            Section::make('Opening Balance Setup')
                ->schema($this->getSubFormSchema())
                ->columns(4)
                ->collapsible()
        ];
    }

    public function getSubFormSchema(): array
    {
        $headers = [
            Placeholder::make('Account'),
            Placeholder::make('Debit'),
            Placeholder::make('Credit'),
            Placeholder::make('Balance'),
        ];

        $footers = [
            Placeholder::make('Total'),
            TextInput::make('data.total_debit')
                ->hiddenLabel()
                ->numeric()
                ->readOnly()
                ->prefix('AED'),
            TextInput::make('data.total_credit')
                ->hiddenLabel()
                ->numeric()
                ->readOnly()
                ->prefix('AED'),
            TextInput::make('data.total_balance')
                ->hiddenLabel()
                ->numeric()
                ->readOnly()
                ->prefix('AED'),
        ];

        $accountFields = [];
        foreach ($this->getAccounts() as $account) {
            $accountFields[] = PlaceHolder::make($account->name)
                ->content($account->formattedLabel)
                ->hiddenLabel();

            $accountFields[] = TextInput::make("data.account.{$account->id}.debit")
                ->hiddenLabel()
                ->currencyMask()
                ->minValue(0)
                ->prefix('AED')
                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($account) {
                    $set("data.account.{$account->id}.credit", 0);
                    $set("data.account.{$account->id}.opening_balance", $state);
                    $this->calculateTotals();
                })
                ->lazy();

            $accountFields[] = TextInput::make("data.account.{$account->id}.credit")
                ->hiddenLabel()
                ->currencyMask()
                ->minValue(0)
                ->prefix('AED')
                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($account) {
                    $set("data.account.{$account->id}.debit", 0);
                    $set("data.account.{$account->id}.opening_balance", -$state);
                    $this->calculateTotals();
                })
                ->lazy();

            $accountFields[] = TextInput::make("data.account.{$account->id}.opening_balance")
                ->hiddenLabel()
                ->currencyMask()
                ->prefix('AED')
                ->readOnly();
        }

        return array_merge($headers, $accountFields, $footers);
    }

    public function calculateTotals(): void
    {
        $totals = ['debit' => 0, 'credit' => 0, 'balance' => 0];

        foreach ($this->getAccounts() as $account) {
            $accountData = $this->data['account'][$account->id] ?? [];
            $totals['debit'] += (float)($accountData['debit'] ?? 0);
            $totals['credit'] += (float)($accountData['credit'] ?? 0);
            $totals['balance'] += (float)($accountData['opening_balance'] ?? 0);
        }

        $this->data['total_debit'] = $totals['debit'];
        $this->data['total_credit'] = $totals['credit'];
        $this->data['total_balance'] = $totals['balance'];
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Opening Balances')
                ->action('save')
                ->color('primary'),
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(AccountResource::getUrl())
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::FiveExtraLarge;
    }

    public function save()
    {
        try {
            DB::transaction(function () {
                $formData = $this->form->getState();

                // Clean up existing entries
                $this->cleanupExistingOpeningBalances();

                // Reset all balances to 0
                $this->resetOpeningBalances();

                // Create new journal entry
                $journalEntry = $this->createOpeningBalanceJournalEntry();

                // Process accounts in optimized batches
                $this->processAccountsInBatches($formData, $journalEntry);

                Notification::make()
                    ->title('Opening balances saved successfully')
                    ->success()
                    ->send();
            });

            return redirect()->to(AccountResource::getUrl());

        } catch (Exception $e) {
            Log::error('Opening balance setup failed: ' . $e->getMessage());

            Notification::make()
                ->title('Error saving opening balances')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    private function cleanupExistingOpeningBalances(): void
    {
        $oldJournalEntry = JournalEntry::with('transactions')
            ->where('description', 'Auto Generated JV for Opening Balance Setup.')
            ->first();

        if ($oldJournalEntry) {
            $accountService = app(AccountService::class);

            // Reverse all transactions
            foreach ($oldJournalEntry->transactions as $transaction) {
                $accountService->applyTransaction(
                    $transaction->account_id,
                    $transaction->credit,
                    $transaction->debit
                );
            }

            // Delete in bulk
            $oldJournalEntry->transactions()->delete();
            $oldJournalEntry->delete();
        }

        // Reset bank account balances
        BankAccount::query()->update(['opening_balance' => 0]);
    }

    private function resetOpeningBalances(): void
    {
        Account::query()->update(['opening_balance' => 0]);
    }

    private function createOpeningBalanceJournalEntry(): JournalEntry
    {
        return JournalEntry::create([
            'entry_date' => now(),
            'reference' => 'JV-' . now()->format('YmdHis'),
            'description' => 'Auto Generated JV for Opening Balance Setup.',
            'entryable_type' => Account::class,
            'entryable_id' => $this->getAccounts()->first()->id,
            'fiscal_year_id' => FiscalYear::getCurrentID(),
            'amount' => 0
        ]);
    }

    private function processAccountsInBatches(array $formData, JournalEntry $journalEntry): void
    {
        $accountService = app(AccountService::class);
        $transactions = [];
        $accountUpdates = [];
        $bankAccountUpdates = [];

        foreach ($this->getAccounts() as $account) {
            $openingBalance = (float)($formData['data']['account'][$account->id]['opening_balance'] ?? 0);

            if ($openingBalance != 0) {
                $accountUpdates[$account->id] = ['opening_balance' => $openingBalance];

                // Prepare bank account update if exists
                if ($bankAccounts = $account->bankAccounts) {
                    foreach ($bankAccounts as $bankAccount) {
                        $oldOpening = (float)$bankAccount->opening_balance;
                        $newOpening = (float)$openingBalance;

                        $adjustedCurrent = $bankAccount->current_balance - $oldOpening + $newOpening;

                        $bankAccountUpdates[$bankAccount->id] = [
                            'opening_balance' => $newOpening,
                            'current_balance' => $adjustedCurrent,
                            'updated_at' => now(),
                        ];
                    }
                }

                // Prepare transaction
                $transactions[] = [
                    'account_id' => $account->id,
                    'debit' => $openingBalance > 0 ? $openingBalance : 0,
                    'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                    'memo' => 'Opening Balance for ' . $account->name,
                    'journal_entry_id' => $journalEntry->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Apply transaction immediately
                $accountService->applyTransaction(
                    $account->id,
                    $openingBalance > 0 ? $openingBalance : 0,
                    $openingBalance < 0 ? abs($openingBalance) : 0
                );
            }
        }

        // Batch update accounts
        if (!empty($accountUpdates)) {
            foreach ($accountUpdates as $id => $data) {
                Account::where('id', $id)->update($data);
            }
        }

        // Batch update bank accounts
        if (!empty($bankAccountUpdates)) {
            foreach ($bankAccountUpdates as $id => $data) {
                BankAccount::where('id', $id)->update($data);
            }
        }

        // Batch insert transactions
        if (!empty($transactions)) {
            DB::table('transactions')->insert($transactions);
        }

        // Update journal entry total
        $journalEntry->update([
            'amount' => array_sum(array_column($transactions, 'debit')) +
                array_sum(array_column($transactions, 'credit'))
        ]);
    }
}
