<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxPaymentResource\Pages;
use App\Filament\Resources\TaxPaymentResource\RelationManagers;
use App\Models\TaxPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxPaymentResource extends Resource
{
    protected static ?string $model = TaxPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tax_report_id')
                    ->relationship('taxReport', 'tax_type')
                    ->required(),
                Forms\Components\Select::make('bank_account_id')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('payment_reference'),
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'check' => 'Check',
                        'cash' => 'Cash',
                        'online' => 'Online Payment',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('taxReport.tax_type')
                    ->label('Tax Type'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_reference'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tax_report_id')
                    ->relationship('taxReport', 'tax_type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxPayments::route('/'),
            'create' => Pages\CreateTaxPayment::route('/create'),
            'edit' => Pages\EditTaxPayment::route('/{record}/edit'),
        ];
    }
}
