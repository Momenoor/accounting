<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Employee extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone',
        'hire_date', 'salary', 'payment_method',
        'bank_account', 'is_active'
    ];

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function name(): Attribute
    {
        return new Attribute(
            get: fn() => $this->first_name . ' ' . $this->last_name,
        );
    }
}
