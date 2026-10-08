<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterSubRincianRetribusi extends Model
{
    use HasFactory;

    protected $table = 'master_sub_rincian_retribusi';

    protected $fillable = ['nama_sub_rincian_retribusi', 'master_rincian_retribusi_id'];

    public function rincianRetribusi()
    {
        return $this->belongsTo(MasterRincianRetribusi::class, 'master_rincian_retribusi_id');
    }

    public function objek()
    {
        return $this->hasMany(MasterObjek::class, 'sub_rincian_retribusi');
    }
}
