<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchOperationalHours extends Model
{
    use HasFactory;

    protected $table = 'branch_operational_hours';

    protected $fillable = [
        'branch_operational_rule_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
        'created_at',
        'updated_at',
    ];

    public function rule()
    {
        return $this->belongsTo(BranchOperationalRule::class, 'branch_operational_rules_id');
    }
}
