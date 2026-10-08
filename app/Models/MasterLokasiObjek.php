<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLokasiObjek extends Model
{
    protected $table = 'master_lokasi_objek';

    protected $with = ['operationalTime'];

    protected $fillable = ['master_opd_id', 'nama_lokasi'];

    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'master_lokasi_id');
    }
    public function users()
    {
        return $this->hasMany(User::class, 'master_lokasi_id');
    }
    public function operationalTime()
    {
        return $this->hasMany(BranchOperationalRule::class, 'master_lokasi_id');
    }
    public function chief()
    {
        return $this->hasMany(MasterChief::class, 'master_lokasi_id');
    }
    public function masterOpd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }
}
