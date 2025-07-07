<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChartOfAccounts extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Assets', Account::where('type', 'asset')->sum('current_balance'))
                ->money(),
            Stat::make('Total Liabilities', Account::where('type', 'liability')->sum('current_balance'))
                ->money(),
            Stat::make('Total Equity', Account::where('type', 'equity')->sum('current_balance'))
                ->money(),
            Stat::make('Net Income',
                Account::where('type', 'revenue')->sum('current_balance') -
                Account::where('type', 'expense')->sum('current_balance'))
                ->money(),
        ];
    }
}
