<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialReport extends Model
{
    protected $fillable = [
        'name', 'type', 'start_date', 'end_date',
        'fiscal_year_id', 'report_data', 'is_published', 'created_by'
    ];

    protected $casts = [
        'report_data' => 'array',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinancialReportItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
