<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = ['master_tipe_dokumen_id', 'transaksi_id', 'installment_id', 'master_chief_id',  'document_number', 'linked_document_number', 'issue_date',  'download_link'];


    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'reference_no');
    }
}
