<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency',
        'currency_name',
        'rate',
        'base_currency',
        'last_updated_at',
    ];

    protected $casts = [
        'rate' => 'decimal:10',
        'last_updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->whereNotNull('rate');
    }

    public function getFormattedRateAttribute()
    {
        return number_format($this->rate, 4);
    }
}
