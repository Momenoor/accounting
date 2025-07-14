<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Transactions & Journals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255)
                    ->default('INV-' . now()->format('YmdHis')),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('issue_date')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->default(now()->addMonth()),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('description')
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('tax_rate_id')
                            ->relationship('taxRate', 'name'),
                    ])
                    ->columns(5)
                    ->defaultItems(1),

                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                ->searchable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_paid')
                    ->icon('heroicon-o-banknotes')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'paid']);

                        // Create journal entry for payment
                        $journalEntry = JournalEntry::create([
                            'entry_date' => now(),
                            'description' => 'Payment for invoice ' . $record->invoice_number,
                            'fiscal_year_id' => FiscalYear::current()->id,
                        ]);

                        $journalEntry->transactions()->createMany([
                            [
                                'account_id' => Account::where('code', '1100')->first()->id, // Cash account
                                'debit' => $record->total_amount,
                                'credit' => 0,
                            ],
                            [
                                'account_id' => Account::where('code', '1200')->first()->id, // Accounts Receivable
                                'debit' => 0,
                                'credit' => $record->total_amount,
                            ],
                        ]);

                        Notification::make()
                            ->title('Invoice marked as paid and accounting entries created')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Invoice $record): bool => $record->status !== 'paid'),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
