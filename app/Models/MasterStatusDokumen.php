<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterStatusDokumen extends Model
{
    protected $table = 'master_status_dokumen';
    protected $fillable = ['nama_status_dokumen'];
    public function transaksi()
    {
        return $this->hasMany(Document::class, 'master_status_dokumen_id');
    }
}
