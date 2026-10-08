<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterTpj extends Model
{
    protected $table = 'master_tpj';
    protected $fillable = ['nama_tpj'];
    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_tpj_id');
    }
}
