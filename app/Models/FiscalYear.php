<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static current()
 */
class FiscalYear extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'is_active'
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true);
    }

    public static function getCurrentID()
    {
        return static::current()->value('id');
    }
}
