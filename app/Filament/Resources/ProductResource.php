<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('cost')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required(),

                // Accounting integration
                Forms\Components\Select::make('inventory_account_id')
                    ->relationship('inventoryAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('cogs_account_id')
                    ->relationship('costOfGoodsSoldAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('last_purchase_cost')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')
                    ->money(),
                Tables\Columns\TextColumn::make('cost')
                    ->money(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust')
                    ->icon('heroicon-o-adjustments-vertical')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data) {
                        $adjustment = $record->inventoryAdjustments()->create([
                            'quantity_change' => $data['quantity'],
                            'movement_type' => 'adjustment',
                            'reason' => $data['reason'],
                            'user_id' => auth()->id(),
                        ]);

                        $record->update([
                            'quantity' => $record->quantity + $data['quantity'],
                        ]);

                        Notification::make()
                            ->title('Inventory adjusted successfully')
                            ->success()
                            ->send();
                    }),
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
            RelationManagers\MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
