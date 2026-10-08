<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterStatusObjek extends Model
{
    protected $table = 'master_status_objek';
    protected $fillable = ['status_objek'];
    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_status_objek_id');
    }
}
