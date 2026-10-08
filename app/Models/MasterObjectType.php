<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterObjectType extends Model
{
    protected $table = 'master_type_object';
    protected $fillable = ['type'];
    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_type_object_id');
    }
}
