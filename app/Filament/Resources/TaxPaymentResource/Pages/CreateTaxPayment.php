<?php

namespace App\Filament\Resources\TaxPaymentResource\Pages;

use App\Filament\Resources\TaxPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxPayment extends CreateRecord
{
    protected static string $resource = TaxPaymentResource::class;
}
