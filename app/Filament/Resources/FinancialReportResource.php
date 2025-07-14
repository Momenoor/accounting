<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialReportResource\Pages;
use App\Filament\Resources\FinancialReportResource\RelationManagers;
use App\Models\FinancialReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FinancialReportResource extends Resource
{
    protected static ?string $model = FinancialReport::class;

    protected static ?string $navigationGroup = 'Financial Reporting';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'balance_sheet' => 'Balance Sheet',
                        'income_statement' => 'Income Statement',
                        'cash_flow' => 'Cash Flow Statement',
                        'trial_balance' => 'Trial Balance',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Select::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'name')
                    ->required(),
                Forms\Components\Toggle::make('is_published')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'balance_sheet' => 'Balance Sheet',
                        'income_statement' => 'Income Statement',
                        'cash_flow' => 'Cash Flow Statement',
                        'trial_balance' => 'Trial Balance',
                        'custom' => 'Custom Report',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->icon('heroicon-o-document-text')
                    ->action(function (FinancialReport $record) {
                        // Generate report logic here
                    }),
                Tables\Actions\Action::make('view')
                    ->url(fn (FinancialReport $record) => route('reports.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFinancialReports::route('/'),
            'create' => Pages\CreateFinancialReport::route('/create'),
            'edit' => Pages\EditFinancialReport::route('/{record}/edit'),
        ];
    }
}
