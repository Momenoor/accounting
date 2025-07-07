<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReportItem extends Model
{
    protected $fillable = [
        'report_id', 'name', 'amount', 'item_type', 'sort_order', 'account_id'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(FinancialReport::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
