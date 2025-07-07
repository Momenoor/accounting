<?php

namespace App\Filament\Resources\PayrollResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required(),
                Forms\Components\TextInput::make('gross_pay')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('tax_amount')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('other_deductions')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('net_pay')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name'),
                Tables\Columns\TextColumn::make('gross_pay')
                    ->money(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->money(),
                Tables\Columns\TextColumn::make('other_deductions')
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
