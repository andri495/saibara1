<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class MasterObjek extends Model
{
    protected $table = 'master_objek';
    protected $fillable = [
        'master_opd_id',
        'master_lokasi_id',
        'uraian_objek',
        'master_retribusi_id',
        'master_rincian_retribusi_id',
        'sub_rincian_retribusi',
        'rincian_objek',
        'sub_rincian_objek',
        'master_role_id',
        'master_satuan_id',
        'master_rules_id',
        'master_tpj_id',
        'harga_objek',
        'harga_objek_asing',
        'master_status_objek_id',
        'required_document',
        'master_type_object_id',
        'images',
        'master_currency_id'
    ];
    protected $with = ['lokasi', 'rules', 'opd', 'retribusi', 'rincianRetribusi', 'subRincianRetribusi', 'satuan', 'tpj', 'statusObjek', 'ratings', 'typeObject', 'currency'];

    protected $casts = [
        'id' => 'integer',
        'master_opd_id' => 'integer',
        'master_lokasi_id' => 'integer',
        'master_retribusi_id' => 'integer',
        'master_rincian_retribusi_id' => 'integer',
        'master_role_id' => 'integer',
        'master_satuan_id' => 'integer',
        'master_rules_id' => 'integer',
        'master_tpj_id' => 'integer',
        'master_status_objek_id' => 'integer',
        'master_type_object_id' => 'integer',
        'master_currency_id' => 'integer',
        'harga_objek' => 'double',
        'harga_objek_asing' => 'double',
    ];

    /**
     * Format the average rating to always show 1 decimal place
     * This accessor will be automatically called when accessing ratings_avg_rating_star
     */

    public function lokasi()
    {
        return $this->belongsTo(MasterLokasiObjek::class, 'master_lokasi_id');
    }
    public function rules()
    {
        return $this->belongsTo(MasterRules::class, 'master_rules_id');
    }
    public function opd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }
    public function retribusi()
    {
        return $this->belongsTo(MasterRetribusi::class, 'master_retribusi_id');
    }
    public function typeObject()
    {
        return $this->belongsTo(MasterObjectType::class, 'master_type_object_id');
    }
    public function rincianRetribusi()
    {
        return $this->belongsTo(MasterRincianRetribusi::class, 'master_rincian_retribusi_id');
    }
    public function subRincianRetribusi()
    {
        return $this->belongsTo(MasterSubRincianRetribusi::class, 'sub_rincian_retribusi');
    }
    public function satuan()
    {
        return $this->belongsTo(MasterSatuan::class, 'master_satuan_id');
    }
    public function tpj()
    {
        return $this->belongsTo(MasterTpj::class, 'master_tpj_id');
    }
    public function statusObjek()
    {
        return $this->belongsTo(MasterStatusObjek::class, 'master_status_objek_id');
    }

    public function currency()
    {
        return $this->belongsTo(MasterCurrency::class, 'master_currency_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'master_objek_id');
    }

    public function cartObject()
    {
        return $this->hasMany(CartObject::class, 'object_id');
    }
    public function transaksiObject()
    {
        return $this->hasMany(TransaksiObjek::class, 'object_id');
    }

    public function scopeWithTransactionCount(Builder $query)
    {
        return $query->addSelect([
            'transaction_counts' => DB::table('transaction_objects')
                ->selectRaw('COUNT(*)')
                ->whereColumn('transaction_objects.object_id', 'master_objek.id')
        ]);
    }
}
