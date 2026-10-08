<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterRules extends Model
{
    use HasFactory;

    public function objects()
    {
        return $this->hasMany(MasterObjek::class, 'master_rules_id');
    }
}
