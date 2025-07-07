<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Filament\Resources\BillResource\RelationManagers;
use App\Models\Account;
use App\Models\Bill;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\TaxRate;
use App\Services\BillService;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'received' => 'Received',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('draft')
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make('Dates')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDays(30)),
                    ]),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($product = Product::find($state)) {
                                            $set('unit_price', $product->last_purchase_cost ?: $product->cost);
                                            $set('description', $product->name);
                                            $set('account_id', $product->inventory_account_id);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->minValue(0.01),

                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
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
                            ->live()
                            ->afterStateHydrated(function ($state, $set, $get) {
                                static::calculateTotals($state, $set);
                            })
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::calculateTotals($state, $set);
                            })
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->readOnly()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->readOnly()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->readOnly()
                            ->prefix('AED')
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
            $itemTotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
            $subtotal += $itemTotal;

            if (!empty($item['tax_rate_id'])) {
                $taxRate = TaxRate::find($item['tax_rate_id']);
                if ($taxRate) {
                    $taxAmount += $itemTotal * ($taxRate->rate / 100);
                }
            }
        }

        $set('subtotal', number_format($subtotal, 2));
        $set('tax_amount', number_format($taxAmount, 2));
        $set('total_amount', number_format($subtotal + $taxAmount, 2));
    }

    protected function saveRelationships(Bill $bill, $state): void
    {

        DB::transaction(function () use ($bill, $state) {
//            $items = collect($state['items'])->map(function ($item) {
//                $itemTotal = $item['quantity'] * $item['unit_price'];
//                $taxRate = TaxRate::find($item['tax_rate_id'] ?? null);
//
//                return [
//                    'product_id' => $item['product_id'],
//                    'quantity' => $item['quantity'],
//                    'unit_price' => $item['unit_price'],
//                    'tax_rate_id' => $item['tax_rate_id'],
//                    'tax_rate' => $taxRate ? $taxRate->rate : null,
//                    'description' => $item['description'],
//                    'total' => $taxRate ?
//                        $itemTotal + ($itemTotal * $taxRate->rate / 100) :
//                        $itemTotal,
//                    'account_id' => Product::find($item['product_id'])->inventory_account_id,
//                ];
//            });
//
//            $bill->items()->createMany($items->toArray());

            // Process inventory and accounting
            $inventoryService = app(InventoryService::class);
            $inventoryService->processBillInventory($bill);

            $accountingService = app(BillService::class);
            $accountingService->createJournalEntryForBill($bill);
        });
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
                    ->money(),
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
                Tables\Actions\Action::make('mark_as_paid')
                    ->icon('heroicon-o-banknotes')
                    ->action(function (Bill $record) {
                        $record->update(['status' => 'paid']);
                        // Create journal entry for payment
                        $journalEntry = JournalEntry::create([
                            'entry_date' => now(),
                            'description' => 'Payment for invoice ' . $record->bill_number,
                            'fiscal_year_id' => FiscalYear::current()->id,
                        ]);

                        $journalEntry->transactions()->createMany([
                            [
                                'account_id' => Account::where('code', '2200')->first()->id, // Accounts payable
                                'debit' => $record->total_amount,
                                'credit' => 0,
                            ],
                            [
                                'account_id' => Account::where('code', '1100')->first()->id, // Cash account
                                'debit' => 0,
                                'credit' => $record->total_amount,
                            ],
                        ]);

                        Notification::make()
                            ->title('Bill marked as paid and accounting entries created')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Bill $record): bool => $record->status !== 'paid'),
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }
}
