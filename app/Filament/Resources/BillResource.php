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
use App\Services\PaymentService;
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
                            ->readOnly()
                            ->dehydrateStateUsing(function ($state) {
                                return is_numeric($state) ? $state : str_replace(',', '', $state);
                            })
                            ->currencyMask()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('tax_amount')
                            ->readOnly()
                            ->dehydrateStateUsing(function ($state) {
                                return is_numeric($state) ? $state : str_replace(',', '', $state);
                            })
                            ->currencyMask()
                            ->prefix('AED')
                            ->columnStart(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->readOnly()
                            ->dehydrateStateUsing(function ($state) {
                                return is_numeric($state) ? $state : str_replace(',', '', $state);
                            })
                            ->currencyMask()
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
            $itemTotal = ($item['quantity'] ?? 0) * (empty($item['unit_price'])?:0);
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
                Tables\Actions\Action::make('Pay')
                    ->button()
                    ->icon('heroicon-o-banknotes')
                    ->form(
                        [
                            Forms\Components\Select::make('bank_account_id')
                                ->relationship('bankTransactions.bankAccount', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->minValue(0.01)
                                ->default(fn(Bill $record) => number_format($record->total_amount - $record->bankTransactions->sum('amount'), 2))
                                ->maxValue(fn(Bill $record) => number_format($record->total_amount - $record->bankTransactions->sum('amount'), 2)),

                            Forms\Components\DatePicker::make('transaction_date')
                                ->required()
                                ->default(now()),

                            Forms\Components\TextInput::make('type')
                                ->readOnly()
                                ->required()
                                ->hidden()
                                ->default('payment'),

                            Forms\Components\TextInput::make('reference')
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]
                    )
                    ->action(function (array $data, Bill $bill) {
                        $bill->update([
                            'status' => $data['amount'] < $bill->total_amount ? 'received' : 'paid',
                        ]);
                        $paymentDate = [
                            'transaction_date' => now(),
                            'bank_account_id' => $data['bank_account_id'],
                            'amount' => $data['amount'],
                            'type' => 'outgoing',
                            'reference' => $data['reference'] ?? "PMT-{$bill->bill_number}",
                            'description' => $data['description'] ?? "Payment for bill# {$bill->bill_number}",
                            'transactionable' => $bill,
                        ];
                        app(PaymentService::class)->recordPayment($paymentDate);
                        Notification::make()
                            ->title('Bill paid and accounting entries created')
                            ->success()
                            ->send();
                        return redirect()->to(BillResource::getUrl('index'));
                    })
                    ->visible(fn(Bill $bill): bool => $bill->status !== 'paid')
                    ->modalHeading(fn(Bill $bill) => "Pay Bill #{$bill->bill_number}")
                    ->modalDescription('Record a payment for this bill')
                    ->modalSubmitActionLabel('Process Payment'),
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
