<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxReportResource\Pages;
use App\Filament\Resources\TaxReportResource\RelationManagers;
use App\Models\TaxReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxReportResource extends Resource
{
    protected static ?string $model = TaxReport::class;

    protected static ?string $navigationGroup = 'Tax Management';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tax_type')
                    ->options([
                        'vat' => 'VAT',
                        'income_tax' => 'Income Tax',
                        'payroll_tax' => 'Payroll Tax',
                        'sales_tax' => 'Sales Tax',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('reporting_period_start')
                    ->required(),
                Forms\Components\DatePicker::make('reporting_period_end')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\TextInput::make('tax_amount')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'filed' => 'Filed',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ])
                    ->default('pending'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tax_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('reporting_period_start')
                    ->date(),
                Tables\Columns\TextColumn::make('reporting_period_end')
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->color(fn ($record) => $record->due_date < now() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->money(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'filed' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tax_type')
                    ->options([
                        'vat' => 'VAT',
                        'income_tax' => 'Income Tax',
                        'payroll_tax' => 'Payroll Tax',
                        'sales_tax' => 'Sales Tax',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->action(function (TaxReport $record) {
                        // Generate tax report data
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
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxReports::route('/'),
            'create' => Pages\CreateTaxReport::route('/create'),
            'edit' => Pages\EditTaxReport::route('/{record}/edit'),
        ];
    }
}
