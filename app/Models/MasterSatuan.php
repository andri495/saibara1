<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSatuan extends Model
{
    protected $table = 'master_satuan';
    protected $fillable = ['nama_satuan'];
    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_satuan_id');
    }
}
