<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetributionTargets extends Model
{
    use HasFactory;

    protected $table = 'retribution_targets';

    protected $fillable = [
        'master_opd_id',
        'master_lokasi_id',
        'master_retribusi_id',
        'master_rincian_retribusi_id',
        'master_sub_rincian_retribusi_id',
        'target_amount',
        'tahun',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'tahun' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function opd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }

    public function retribusi()
    {
        return $this->belongsTo(MasterRetribusi::class, 'master_retribusi_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(MasterLokasiObjek::class, 'master_lokasi_id');
    }

    public function rincianRetribusi()
    {
        return $this->belongsTo(MasterRincianRetribusi::class, 'master_rincian_retribusi_id');
    }

    public function subRincianRetribusi()
    {
        return $this->belongsTo(MasterSubRincianRetribusi::class, 'master_sub_rincian_retribusi_id');
    }
}
