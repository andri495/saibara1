<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory;

    protected $table = 'revenues';

    protected $fillable = [
        'date', 'year', 'month',
        'master_opd_id',
        'master_lokasi_id',
        'master_retribusi_id',
        'master_rincian_retribusi_id',
        'master_sub_rincian_retribusi_id',
        'total_transactions',
        'total_revenue',
        'is_installment',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'total_transactions' => 'integer',
        'total_revenue' => 'decimal:2',
        'is_installment' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships

    /**
     * Get the OPD (Organisasi Perangkat Daerah) that owns this revenue record
     */
    public function opd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }

    /**
     * Get the retribution type that owns this revenue record
     */
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

    public function objek()
    {
        return $this->belongsTo(MasterObjek::class, 'master_objek_id');
    }

    // Scopes

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by OPD
     */
    public function scopeByOpd($query, $opdId)
    {
        return $query->where('master_opd_id', $opdId);
    }

    /**
     * Scope to filter by retribution type
     */
    public function scopeByRetribusi($query, $retribusiId)
    {
        return $query->where('master_retribusi_id', $retribusiId);
    }

    /**
     * Scope to filter by installment status
     */
    public function scopeInstallment($query, $isInstallment = true)
    {
        return $query->where('is_installment', $isInstallment);
    }

    /**
     * Scope to get regular (non-installment) revenues
     */
    public function scopeRegular($query)
    {
        return $query->where('is_installment', 0);
    }

    /**
     * Scope to filter by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Scope to filter by month
     */
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }
}
