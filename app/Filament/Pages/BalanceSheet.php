<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalYear;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class BalanceSheet extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static string $view = 'filament.pages.balance-sheet';
    protected static ?string $navigationGroup = 'Financial Reports';

    public $asOfDate;
    public $startDate;
    public $endDate;

    public function mount(): void
    {
        $this->form->fill([
            'asOfDate' => now()->format('Y-m-d'),
            'startDate' => now()->startOfYear()->format('Y-m-d'),
            'endDate' => now()->format('Y-m-d'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('asOfDate')
                ->label('As Of Date')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateTableData()),

            DatePicker::make('startDate')
                ->label('From Date')
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateTableData()),

            DatePicker::make('endDate')
                ->label('To Date')
                ->reactive()
                ->afterStateUpdated(fn () => $this->updateTableData()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBalanceSheetQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Account')
                    ->sortable(),

                TextColumn::make('opening_balance')
                    ->label('Opening Balance')
                    ->money('USD')
                    ->color(fn ($record) => $record->opening_balance < 0 ? 'danger' : 'success'),

                TextColumn::make('period_change')
                    ->label('Period Change')
                    ->money('USD')
                    ->color(fn ($record) => $record->period_change < 0 ? 'danger' : 'success'),

                TextColumn::make('ending_balance')
                    ->label('Ending Balance')
                    ->money('USD')
                    ->color(fn ($record) => $record->ending_balance < 0 ? 'danger' : 'success'),
            ])
            ->groups([
                'type' => fn ($record) => ucfirst($record->type),
            ])
            ->paginated(false);
    }

    protected function getBalanceSheetQuery()
    {
        $asOfDate = $this->asOfDate ?? now();
        $startDate = $this->startDate ?? FiscalYear::getCurrent()->start_date;
        $endDate = $this->endDate ?? now();

        return Account::query()
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.code',
                'accounts.type',
                'accounts.opening_balance',
                DB::raw('accounts.opening_balance + COALESCE(SUM(CASE WHEN transactions.created_at <= ? THEN transactions.debit - transactions.credit ELSE 0 END), 0) as ending_balance'),
                DB::raw('COALESCE(SUM(CASE WHEN transactions.created_at BETWEEN ? AND ? THEN transactions.debit - transactions.credit ELSE 0 END), 0) as period_change')
            ])
            ->leftJoin('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->setBindings([$asOfDate, $startDate, $endDate])
            ->groupBy('accounts.id')
            ->orderBy('accounts.code');
    }

    protected function updateTableData(): void
    {
        $this->asOfDate = $this->form->getState()['asOfDate'];
        $this->startDate = $this->form->getState()['startDate'];
        $this->endDate = $this->form->getState()['endDate'];

        $this->resetTable();
    }

    public function getTotal(string $type): string
    {
        $total = $this->getBalanceSheetQuery()
            ->where('accounts.type', $type)
            ->get()
            ->sum('ending_balance');

        return number_format($total, 2);
    }

    public function isBalanced(): bool
    {
        $assets = $this->getBalanceSheetQuery()
            ->where('accounts.type', 'asset')
            ->get()
            ->sum('ending_balance');

        $liabilities = $this->getBalanceSheetQuery()
            ->where('accounts.type', 'liability')
            ->get()
            ->sum('ending_balance');

        $equity = $this->getBalanceSheetQuery()
            ->where('accounts.type', 'equity')
            ->get()
            ->sum('ending_balance');

        return abs($assets - ($liabilities + $equity)) < 0.01;
    }
}
