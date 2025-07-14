<?php

namespace App\Filament\Pages;

use App\Models\Account;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class BalanceSheet extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static string $view = 'filament.pages.balance-sheet';
    protected static ?string $navigationGroup = 'Financial Reporting';

    public $asOfDate;
    public $startDate;
    public $endDate;

    protected array $assets = [];
    protected array $liabilities = [];
    protected array $equity = [];
    protected array $revenues = [];
    protected array $expenses = [];

    public function mount(): void
    {
        $today = now()->endOfDay()->format('Y-m-d H:i:s');

        $this->form->fill([
            'asOfDate' => $today,
        ]);

        $this->asOfDate = $today;
        $this->startDate = Carbon::parse($today)->startOfYear()->startOfDay()->format('Y-m-d H:i:s');
        $this->endDate = $this->asOfDate;
    }

    protected function getFormSchema(): array
    {
        return [
            DateTimePicker::make('asOfDate')
                ->label('As Of Date')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->refreshData()),
        ];
    }

    protected function refreshData(): void
    {
        $state = $this->form->getState();
        $this->asOfDate = Carbon::parse($state['asOfDate'])->endOfDay()->format('Y-m-d H:i:s') ?? now()->endOfDay()->format('Y-m-d H:i:s');

        $this->startDate = Carbon::parse($this->asOfDate)->startOfYear()->startOfDay()->format('Y-m-d H:i:s');
        $this->endDate = Carbon::parse($this->asOfDate)->format('Y-m-d H:i:s');

        $this->assets = $this->getAccountsByType('asset');
        $this->liabilities = $this->getAccountsByType('liability');
        $this->equity = $this->getAccountsByType('equity');
        $this->revenues = $this->getAccountsByType('revenue');
        $this->expenses = $this->getAccountsByType('expense');
    }

    protected function getAccountsByType(string $type): array
    {
        $asOfDate = Carbon::parse($this->asOfDate)->endOfDay()->format('Y-m-d H:i:s');
        $startDate = Carbon::parse($this->asOfDate)->startOfYear()->format('Y-m-d H:i:s');
        $endDate = Carbon::parse($this->asOfDate)->endOfDay()->format('Y-m-d H:i:s');

        return Account::query()
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.code',
                'accounts.type',
                'accounts.opening_balance',
                DB::raw("
            accounts.opening_balance
            + COALESCE(SUM(CASE
                WHEN transactions.created_at <= '{$asOfDate}'
                AND (transactions.memo IS NULL OR transactions.memo NOT LIKE '%opening%')
                THEN transactions.debit - transactions.credit
                ELSE 0 END), 0) AS ending_balance
        "),
                DB::raw("
            COALESCE(SUM(CASE
                WHEN transactions.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                    AND (transactions.memo IS NULL OR transactions.memo NOT LIKE '%opening%')
                THEN transactions.debit - transactions.credit
                ELSE 0 END), 0) AS period_change
        "),
            ])
            ->leftJoin('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.type', $type)
            ->groupBy('accounts.id', 'accounts.name', 'accounts.code', 'accounts.type', 'accounts.opening_balance')
            ->orderBy('accounts.code')
            ->get()
            ->toArray();
    }


    public function getTotal(string $type): float
    {
        $map = [
            'asset' => 'assets',
            'liability' => 'liabilities',
            'equity' => 'equity',
            'revenue' => 'revenues',
            'expense' => 'expenses',
        ];

        $property = $map[$type] ?? $type . 's';

        return collect($this->{$property} ?? [])->sum('ending_balance');
    }

    public function isBalanced(): bool
    {
        $assets = $this->getTotal('asset');
        $liabilities = $this->getTotal('liability');
        $equity = $this->getTotal('equity');

        return abs($assets - ($liabilities + $equity)) < 0.01;
    }

    public function getNetProfitLoss(): float
    {
        return $this->getTotal('revenue') - $this->getTotal('expense');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        if (empty($this->assets)) {
            $this->refreshData();
        }

        return view(static::$view, [
            'assets' => $this->assets,
            'liabilities' => $this->liabilities,
            'equity' => $this->equity,
            'revenues' => $this->revenues,
            'expenses' => $this->expenses,
            'isBalanced' => $this->isBalanced(),
            'totalAssets' => number_format($this->getTotal('asset'), 2),
            'totalLiabilities' => number_format($this->getTotal('liability'), 2),
            'totalEquity' => number_format($this->getTotal('equity'), 2),
            'totalRevenue' => number_format($this->getTotal('revenue'), 2),
            'totalExpense' => number_format($this->getTotal('expense'), 2),
            'netProfitLoss' => number_format($this->getNetProfitLoss(), 2),
        ])->layout($this->getLayout());
    }
}
