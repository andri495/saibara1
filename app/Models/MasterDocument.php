<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDocument extends Model
{
    protected $table = 'master_document';
    protected $fillable = ['nama_document'];
    public function documemt()
    {
        return $this->hasMany(Document::class, 'master_tipe_dokumen_id');
    }
}
