<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{

    protected $fillable = [
        'entry_date', 'reference', 'description', 'fiscal_year_id', 'entryable_type', 'entryable_id',
    ];


    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function fiscalYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function entryable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

}
