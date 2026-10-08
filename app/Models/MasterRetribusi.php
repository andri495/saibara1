<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterRetribusi extends Model
{
    protected $table = 'master_retribusi';
    protected $fillable = ['nama_retribusi'];
    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_retribusi_id');
    }

    public function rincianRetribusi()
    {
        return $this->hasMany(MasterRincianRetribusi::class, 'master_tipe_retribusi_id');
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class, 'master_retribusi_id');
    }

    public function retributionTargets()
    {
        return $this->hasMany(RetributionTargets::class, 'master_retribusi_id');
    }
}
