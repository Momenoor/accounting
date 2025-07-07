<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    protected $fillable = [
        'name', 'rate', 'code', 'is_active'
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}
