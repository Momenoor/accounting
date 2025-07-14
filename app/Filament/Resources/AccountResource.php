<?php

namespace App\Filament\Resources;

use App\Filament\Exports\AccountExporter;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Accounting & Financial Setup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ])
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('opening_balance')
                    ->numeric()
                    ->readonly()
                    ->default(0),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('code'),
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn($state, $record) => str_repeat('— ', $record->depth) . $state),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'primary',
                        'revenue' => 'info',
                        'expense' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('current_balance')
                    ->formatStateUsing(function ($state) {
                        return $state < 0
                            ? '(' . number_format(abs((float)$state), 2) . ')'
                            : number_format((float)$state, 2);
                    })
                    ->currency('AED')
                    ->color(fn($state) => $state < 0 ? Color::Red : ($state > 0 ? Color::Green : Color::Gray)),
                Tables\Columns\TextColumn::make('parent.name'),
            ])
            ->pushHeaderActions([
                Tables\Actions\Action::make('Opening Balance Setup')
                    ->color(Color::Green)
                    ->url(route('filament.admin.pages.opening-balance-setup')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChildrenRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withDepth()->defaultOrder(); // Orders by lft (tree structure)
    }
}
