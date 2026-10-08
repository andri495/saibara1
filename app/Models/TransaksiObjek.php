<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiObjek extends Model
{
    protected $table = 'transaction_objects';

    protected $fillable = [
        'transaction_id',
        'object_id',
        'quantity',
        'tpj_quantity',
        'satuan_quantity',
        'total_amount',
        'document_support_link',
        'document_result',
        'document_payment_link',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',

    ];

    protected $with = ['object'];

    public function transaksiObject()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'reference_no');
    }

    public function object()
    {
        return $this->belongsTo(MasterObjek::class, 'object_id');
    }
}
