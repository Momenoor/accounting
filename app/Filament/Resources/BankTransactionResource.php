<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankTransactionResource\Pages;
use App\Filament\Resources\BankTransactionResource\RelationManagers;
use App\Models\BankTransaction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankTransactionResource extends Resource
{
    protected static ?string $model = BankTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Banking & Cash Management';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Transaction Details')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->columnSpan(1),

                        Select::make('type')
                            ->label('Transaction Type')
                            ->options([
                                'deposit' => 'Deposit',
                                'withdrawal' => 'Withdrawal',
                                'transfer' => 'Transfer',
                                'fees' => 'Fees',
                            ])
                            ->required()
                            ->live()
                            ->disabled(fn($livewire) => $livewire instanceof Pages\EditBankTransaction)
                            ->columnSpan(1),

                        TextInput::make('reference')
                            ->label('Reference Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->columnSpan(2)
                            ->default('BNK-' . now()->timestamp),

                        TextInput::make('amount')
                            ->label('Amount (AED)')
                            ->required()
                            ->readOnly()
                            ->currencyMask()
                            ->prefix('AED')
                            ->default(0)
                            ->minValue(0.01)
                            ->columnSpan(2),
                    ]),

                Section::make('Bank Account Information')
                    ->columns(2)
                    ->schema([
                        Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->relationship('bankAccount', 'bank_name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->bank_name} - {$record->account_number}")
                            ->searchable(['bank_name', 'account_number'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $bankAccount = \App\Models\BankAccount::find($state);
                                $set('account_number', $bankAccount?->account_number);
                                $set('bank_name', $bankAccount?->bank_name);
                                $set('current_balance', $bankAccount?->current_balance);
                            })
                            ->afterStateHydrated(function ($state, Forms\Set $set) {
                                $bankAccount = \App\Models\BankAccount::find($state);
                                $set('account_number', $bankAccount?->account_number);
                                $set('bank_name', $bankAccount?->bank_name);
                                $set('current_balance', $bankAccount?->current_balance);
                            })
                            ->hint(function ($component) {
                                $state = $component->getState();
                                if (!$state) return null;
                                $balance = \App\Models\BankAccount::find($state)?->current_balance;
                                return $balance ? 'Current Balance: AED ' . number_format($balance, 2) : null;
                            })
                            ->columnSpan(2),

                        TextInput::make('account_number')
                            ->label('Account Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('current_balance')
                            ->label('Current Balance')
                            ->disabled()
                            ->currencyMask()
                            ->prefix('AED')
                            ->dehydrated(false)
                            ->columnSpan(2),
                    ]),

                Section::make('Counterparty Information')
                    ->schema([
                        Forms\Components\Repeater::make('transactionItems')
                            ->label('Counterparty')
                            ->schema([
                                Select::make('account_id')
                                    ->label(fn(Forms\Get $get): string => $get('../../type') === 'deposit'
                                        ? 'Source Account'
                                        : 'Destination Account')
                                    ->relationship('account', 'name', fn($query) => $query->whereIsLeaf()->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->formattedLabel)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                ,
                                TextInput::make('total')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->currencyMask()
                                    ->prefix('AED')
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $items = $get('../../transactionItems') ?? [];
                                        $total = collect($items)
                                            ->pluck('total')
                                            ->map(fn($val) => (float)($val ?? 0))
                                            ->sum();
                                        $set('../../amount', $total);
                                    }),
                                TextInput::make('description')
                                    ->columnSpan(2),
                            ])
                            ->relationship()
                            ->columns(4)
                    ])
                    ->hidden(fn(Forms\Get $get): bool => !$get('type')),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('description')
                            ->label('Description/Notes')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'danger',
                        'fee' => 'warning',
                        'interest' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('amount')
                    ->money('AED')
                    ->sortable()
                    ->color(fn(string $state, BankTransaction $record): string => in_array($record->type, ['deposit', 'interest']) ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                        'fee' => 'Fee',
                        'interest' => 'Interest',
                    ]),

                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->relationship('bankAccount', 'name'),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when($data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankTransactions::route('/'),
            'create' => Pages\CreateBankTransaction::route('/create'),
            'edit' => Pages\EditBankTransaction::route('/{record}/edit'),
        ];
    }
}
