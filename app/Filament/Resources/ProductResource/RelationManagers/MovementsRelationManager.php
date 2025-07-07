<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('quantity_change')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('movement_type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'transfer' => 'Transfer',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('movement_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'transfer' => 'Transfer',
                    ]),
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
