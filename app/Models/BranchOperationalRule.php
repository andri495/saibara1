<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchOperationalRule extends Model
{
    use HasFactory;

    protected $table = 'branch_operational_rules';

    protected $with = ['hours'];

    protected $fillable = [
        'master_lokasi_id',
        'rule_name',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function lokasi()
    {
        return $this->belongsTo(MasterLokasiObjek::class, 'master_lokasi_id');
    }

    public function hours()
    {
        return $this->hasMany(BranchOperationalHours::class, 'branch_operational_rules_id');
    }
}
