<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FiscalYearResource\Pages;
use App\Filament\Resources\FiscalYearResource\RelationManagers;
use App\Models\FiscalYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->action(function (FiscalYear $record) {
                        // Deactivate all other fiscal years
                        FiscalYear::where('id', '!=', $record->id)->update(['is_active' => false]);
                        $record->update(['is_active' => true]);
                    })
                    ->visible(fn (FiscalYear $record): bool => !$record->is_active),
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
            RelationManagers\JournalEntriesRelationManager::class,
            RelationManagers\BudgetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiscalYears::route('/'),
            'create' => Pages\CreateFiscalYear::route('/create'),
            'edit' => Pages\EditFiscalYear::route('/{record}/edit'),
        ];
    }
}
