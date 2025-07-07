<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payroll_number')
                    ->default('PAY-' . now()->format('Ym') . '-' . strtoupper(Str::random(4)))
                    ->required(),
                Forms\Components\DatePicker::make('payment_date')
                    ->required(),
                Forms\Components\Select::make('pay_period_year')
                    ->options(function () {
                        $currentYear = (int)now()->format('Y');
                        return collect(range($currentYear - 2, $currentYear + 3))
                            ->mapWithKeys(fn ($year) => [$year => $year])
                            ->toArray();
                    })
                    ->default(now()->format('Y'))
                    ->required()
                ,
                Forms\Components\Select::make('pay_period_month')
                    ->options(collect(range(1, 12))
                        ->mapWithKeys(fn ($month) => [$month => Carbon::create()->month($month)->format('F')])
                        ->toArray())
                    ->default(now()->format('n'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft'),

                Forms\Components\Repeater::make('items')
                    ->label('Staff List')
                    ->relationship()
                    ->default(
                        function () {
                            return Employee::query()->where('is_active', true)
                                ->get()
                                ->map(function ($employee) {
                                    return [
                                        'employee_id' => $employee->id,
                                        'gross_pay' => $employee->salary,
                                        'other_deductions' => 0,
                                        'net_pay' => $employee->salary,
                                    ];
                                });
                        }
                    )
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(function (Employee $employee) {
                                return $employee->first_name . ' ' . $employee->last_name;
                            })
                            ->disabled() // since it's prefilled
                            ->dehydrated(), // store it in DB

                        TextInput::make('gross_pay')
                            ->numeric()
                            ->required()
                            ->reactive(),

                        TextInput::make('other_deductions')
                            ->label('Other Deductions')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $netPay = ($get('gross_pay') ?? 0) - $state;
                                $set('net_pay', $netPay);
                            }),

                        TextInput::make('net_pay')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->addable(false)
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll_number'),
                Tables\Columns\TextColumn::make('pay_period_start')
                    ->date(),
                Tables\Columns\TextColumn::make('pay_period_end')
                    ->date(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processed' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('net_pay')
                    ->money(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('process')
                    ->action(function (Payroll $record) {
                        $record->update(['status' => 'processed']);
                        $journalEntry = $record->journalEntry()->create([
                            'entry_date' => $record->payment_date,
                            'description' => 'Payroll for period ' . $record->pay_period_year . ' - ' . $record->pay_period_month,
                            'fiscal_year_id' => $record->fiscal_year_id,
                        ]);

                        // Salary expense
                        $journalEntry->transactions()->create([
                            'account_id' => $record->salary_expense_account_id,
                            'debit' => $record->total_amount,
                            'credit' => 0,
                        ]);

                        // Tax payable
                        $journalEntry->transactions()->create([
                            'account_id' => $record->tax_payable_account_id,
                            'debit' => 0,
                            'credit' => $record->total_tax,
                        ]);

                        // Net pay (bank or cash)
                        $journalEntry->transactions()->create([
                            'account_id' => $record->payment_account_id,
                            'debit' => 0,
                            'credit' => $record->net_pay,
                        ]);

                        Notification::make()
                            ->title('Payroll processed successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Payroll $record): bool => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
