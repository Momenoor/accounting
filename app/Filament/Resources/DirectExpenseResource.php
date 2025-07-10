<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectExpenseResource\Pages;
use App\Filament\Resources\DirectExpenseResource\RelationManagers;
use App\Models\Bill;
use App\Models\DirectExpense;
use App\Models\Product;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DirectExpenseResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Direct Expense';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->relationship('bankTransactions.bankAccount', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->default('Payment for Direct Expenses')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Bill Information')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('bill_number')
                            ->default('BILL-' . now()->format('YmdHis'))
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                    ]),

                Forms\Components\Section::make('Expenses')
                    ->schema([
                        Forms\Components\Repeater::make('expenses')
                            ->relationship('expenses')
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->relationship('account', 'name', function ($query) {
                                        $query->where('type', 'expense')
                                            ->whereNotExists(function ($subquery) {
                                                $subquery->selectRaw(1)
                                                    ->from('accounts as children')
                                                    ->whereColumn('children.parent_id', 'accounts.id');
                                            })
                                            ->orderBy('code');
                                    })
                                    ->exists('accounts', 'id')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive(),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->minValue(0.01),

                                Forms\Components\TextInput::make('unit_price')
                                    ->currencyMask()
                                    ->prefix('AED')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->minValue(0),

                                Forms\Components\Select::make('tax_rate_id')
                                    ->relationship('taxRate', 'name')
                                    ->preload()
                                    ->reactive(),

                                Forms\Components\TextInput::make('description'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->afterStateUpdated(function ($state, $set) {
                                static::calculateTotals($state, $set);
                            })
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->readOnly()
                            ->reactive()
                            ->currencyMask()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('tax_amount')
                            ->readOnly()
                            ->currencyMask()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->readOnly()
                            ->currencyMask()
                            ->prefix('AED')
                            ->afterStateHydrated(function ($state, $set, $get) {
                                $set('subtotal', $get('total_amount') - $get('tax_amount'));
                            })
                            ->columnStart(1),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calculateTotals($items, $set): void
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 0;
            $unitPrice = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
            $itemTotal = $quantity * $unitPrice;
            $subtotal += $itemTotal;

            if (!empty($item['tax_rate_id'])) {
                $taxRate = TaxRate::find($item['tax_rate_id']);
                if ($taxRate) {
                    $taxAmount += $itemTotal * ($taxRate->rate / 100);
                }
            }
        }

        $set('subtotal', $subtotal);
        $set('tax_amount', $taxAmount);
        $set('total_amount', $subtotal + $taxAmount);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'received' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('AED'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'received' => 'Received',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'name'),
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
            'index' => Pages\ListDirectExpenses::route('/'),
            'create' => Pages\CreateDirectExpense::route('/create'),
            'edit' => Pages\EditDirectExpense::route('/{record}/edit'),
        ];
    }


}
