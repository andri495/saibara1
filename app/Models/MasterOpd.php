<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterOpd extends Model
{
    protected $table = 'master_opd';

    protected $fillable = ['nama_opd'];

    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_opd_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'master_opd_id');
    }

    public function chief()
    {
        return $this->hasMany(MasterChief::class, 'master_opd_id');
    }

    public function lokasi()
    {
        return $this->hasMany(MasterLokasiObjek::class, 'master_opd_id');
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class, 'master_opd_id');
    }

    public function retributionTargets()
    {
        return $this->hasMany(RetributionTargets::class, 'master_opd_id');
    }
}
