<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxReport extends Model
{
    protected $fillable = [
        'tax_type', 'reporting_period_start', 'reporting_period_end',
        'due_date', 'tax_amount', 'paid_amount', 'status', 'notes'
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(TaxPayment::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
