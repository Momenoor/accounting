<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'payroll_number', 'pay_period_start', 'pay_period_end',
        'payment_date', 'status', 'total_amount', 'total_tax',
        'total_deductions', 'net_pay'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
