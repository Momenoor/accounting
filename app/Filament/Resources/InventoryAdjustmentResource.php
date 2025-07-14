<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAdjustmentResource\Pages;
use App\Filament\Resources\InventoryAdjustmentResource\RelationManagers;
use App\Models\InventoryAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryAdjustmentResource extends Resource
{
    protected static ?string $model = InventoryAdjustment::class;

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('adjustment_number')
                    ->default('ADJ-' . now()->format('YmdHis'))
                    ->required(),
                Forms\Components\DatePicker::make('adjustment_date')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft'),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('unit_cost')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('direction')
                            ->options([
                                'increase' => 'Increase',
                                'decrease' => 'Decrease',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_number'),
                Tables\Columns\TextColumn::make('adjustment_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'info',
                        'processed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->action(function (InventoryAdjustment $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    })
                    ->visible(fn (InventoryAdjustment $record): bool => $record->status === 'draft'),
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
            'index' => Pages\ListInventoryAdjustments::route('/'),
            'create' => Pages\CreateInventoryAdjustment::route('/create'),
            'edit' => Pages\EditInventoryAdjustment::route('/{record}/edit'),
        ];
    }
}
