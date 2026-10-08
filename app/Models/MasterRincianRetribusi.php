<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterRincianRetribusi extends Model
{
    protected $table = 'master_rincian_retribusi';
    protected $fillable = ['rincian_retribusi', 'master_tipe_retribusi_id'];

    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_rincian_retribusi_id');
    }

    public function retribusi()
    {
        return $this->belongsTo(MasterRetribusi::class, 'master_tipe_retribusi_id');
    }

    public function subRincianRetribusi()
    {
        return $this->hasMany(MasterSubRincianRetribusi::class, 'master_rincian_retribusi_id');
    }
}
