<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterChief extends Model
{
    use HasFactory;

    protected $table = 'master_chief';

    protected $with = ['masterOpd', 'masterRole'];

    protected $fillable = [
        'chief_name',
        'chief_nip',
        'master_opd_id',
        'master_role_id',
    ];

    public function masterOpd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }
    public function masterLokasi()
    {
        return $this->belongsTo(MasterLokasiObjek::class, 'master_lokasi_id');
    }
    public function masterRole()
    {
        return $this->belongsTo(MasterRole::class, 'master_role_id');
    }
}
