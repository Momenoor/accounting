<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('entry_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\Select::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'name')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535),

                Forms\Components\Repeater::make('transactions')
                    ->relationship()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('debit')
                            ->currencyMask()
                            ->prefix('AED')
                            ->default(0),
                        Forms\Components\TextInput::make('credit')
                            ->currencyMask()
                            ->prefix('AED')
                            ->default(0),
                        Forms\Components\Textarea::make('memo')
                            ->maxLength(65535),
                    ])
                    ->columns(3)
                    ->defaultItems(2)
                    ->minItems(2)
                    ->itemLabel(fn (array $state): ?string => $state['account_id'] ?? null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->date(),
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('fiscalYear.name'),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Entries'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'name'),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (JournalEntry $record): string => route('journal-entries.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }

    public static function afterCreate(JournalEntry $record): void
    {
        $debits = $record->transactions()->sum('debit');
        $credits = $record->transactions()->sum('credit');

        if ($debits != $credits) {
            throw ValidationException::withMessages([
                'transactions' => 'The total debits must equal the total credits.',
            ]);
        }

        // Update account balances
        foreach ($record->transactions as $transaction) {
            $account = $transaction->account;
            $account->current_balance += ($transaction->debit - $transaction->credit);
            $account->save();
        }
    }
}
