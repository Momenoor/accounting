<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Filament\Resources\BudgetResource\RelationManagers;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Budgeting & Expense Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Select::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('fiscalYear.name'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('report')
                    ->url(fn ($record) => BudgetResource::getUrl('report', ['record' => $record])),
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
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
