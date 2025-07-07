<?php

namespace App\Filament\Resources\FiscalYearResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class JournalEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('entry_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('reference'),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->date(),
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Entries'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
