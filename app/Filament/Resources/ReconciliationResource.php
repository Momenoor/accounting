<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReconciliationResource\Pages;
use App\Filament\Resources\ReconciliationResource\RelationManagers;
use App\Models\Reconciliation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReconciliationResource extends Resource
{
    protected static ?string $model = Reconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Banking & Cash Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
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
            'index' => Pages\ListReconciliations::route('/'),
            'create' => Pages\CreateReconciliation::route('/create'),
            'edit' => Pages\EditReconciliation::route('/{record}/edit'),
        ];
    }
}
