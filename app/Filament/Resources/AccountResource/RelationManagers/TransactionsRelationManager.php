<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'description')
                    ->required(),
                Forms\Components\TextInput::make('debit')
                    ->numeric(),
                Forms\Components\TextInput::make('credit')
                    ->numeric(),
                Forms\Components\Textarea::make('memo')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('journalEntry.entry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.description')
                    ->limit(30),
                Tables\Columns\TextColumn::make('debit')
                    ->money(),
                Tables\Columns\TextColumn::make('credit')
                    ->money(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
