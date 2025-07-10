<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Filament\Resources\InventoryMovementResource\RelationManagers;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('quantity_change')
                    ->numeric()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        $set('total_cost', $get('unit_cost') * $get('quantity_change'));
                    }),

                Forms\Components\Select::make('movement_type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                        'transfer' => 'Transfer',
                        'waste' => 'Waste',
                    ])
                    ->required(),

                Forms\Components\Select::make('reference_type')
                    ->options([
                        'App\Models\Bill' => 'Bill',
                        'App\Models\Invoice' => 'Invoice',
                        'App\Models\InventoryAdjustment' => 'Adjustment',
                    ])
                    ->searchable(),

                Forms\Components\TextInput::make('reference_id')
                    ->numeric()
                    ->label('Reference ID'),

                Forms\Components\TextInput::make('unit_cost')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        $set('total_cost', $get('unit_cost') * $get('quantity_change'));
                    }),

                Forms\Components\TextInput::make('total_cost')
                    ->numeric()
                    ->prefix('AED')
                    ->disabled()
                    ->dehydrated(),


                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('occurred_at')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity_change')
                    ->sortable()
                    ->label('Qty +/-')
                    ->color(fn(float $state): string => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn(float $state): string => $state >= 0 ? "+$state" : "$state"),

                Tables\Columns\TextColumn::make('movement_type')
                    ->badge()
                    ->label('Type')
                    ->color(fn(string $state): string => match ($state) {
                        'purchase' => 'info',
                        'sale' => 'warning',
                        'adjustment' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->money('AED')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->money('AED')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Ref Type')
                    ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : 'Manual'),

                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->formatStateUsing(function ($state, InventoryMovement $record) {
                        if (!$record->reference_type) return 'N/A';

                        $reference = $record->reference;
                        return $reference ? ($reference->reference_number ?? $reference->bill_number) : 'Deleted';
                    }),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                    ]),

                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('positive_movements')
                    ->label('Only Positive Movements')
                    ->query(fn(Builder $query): Builder => $query->where('quantity_change', '>', 0)),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('occurred_at', 'desc');
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
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'view' => Pages\ViewInventoryMovement::route('/{record}'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
