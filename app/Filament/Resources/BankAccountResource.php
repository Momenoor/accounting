<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Resources\BankAccountResource\RelationManagers;
use App\Models\Account;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Banking & Cash Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('bank_name')
                    ->required(),
                Forms\Components\TextInput::make('account_number')
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload()
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('opening_balance', $set('opening_balance', Account::find($state)?->current_balance ?? 0));
                    }),
                Forms\Components\TextInput::make('opening_balance')
                    ->currencyMask()
                    ->reactive()
                    ->prefix('AED')
                    ->readonly()
                    ->required(),
                Forms\Components\TextInput::make('currency')
                    ->default('AED'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('current_balance')
                    ->money(fn($record) => $record->currency)
                    ->color(fn($state) => $state > 0 ?: Color::Red),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reconcile')
                    ->url(fn($record) => BankAccountResource::getUrl('reconcile', ['record' => $record])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ReconciliationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
            'reconcile' => Pages\ReconcileBankAccount::route('/{record}/reconcile'),
        ];
    }
}
