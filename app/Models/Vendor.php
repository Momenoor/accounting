<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'tax_id'
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
