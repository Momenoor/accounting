<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrollItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payroll_id')
                    ->relationship('payroll', 'payroll_number')
                    ->required(),
                Forms\Components\TextInput::make('gross_pay')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('tax_amount')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('net_pay')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll.payroll_number'),
                Tables\Columns\TextColumn::make('payroll.pay_period_start')
                    ->date(),
                Tables\Columns\TextColumn::make('gross_pay')
                    ->money(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->money(),
                Tables\Columns\TextColumn::make('net_pay')
                    ->money(),
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
