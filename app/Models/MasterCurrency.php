<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCurrency extends Model
{
    use HasFactory;
    protected $table = 'master_currencies';

    protected $fillable = [
        'currency',
        'currency_name',
        'rate',
        'base_currency',
    ];

    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_currency_id');
    }
}
