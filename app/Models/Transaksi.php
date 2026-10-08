<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transactions';

    protected $with = ['document', 'transaksiObject'];

    protected $fillable = ['reference_no', 'subject_id', 'transaction_type', 'npwrd', 'subject_name', 'subject_address', 'nama_instansi', 'no_telp',  'penalty_amount', 'total_amount', 'registration_date', 'payment_date', 'due_date',  'master_tipe_pembayaran_id', 'transaction_response', 'master_status_pembayaran_id',  'document_payment_link',  'va', 'qr_url', 'kode_bayar', 'installment_transaction_id', 'is_installment', 'installment_completed', 'used_time'];

    protected $casts = [
        'is_installment' => 'integer',
        'installment_completed' => 'integer',
        'penalty_amount' => 'decimal:2',  // Changed from integer
        'total_amount' => 'decimal:2',    // Changed from integer
        'subject_id' => 'integer',
        'master_tipe_pembayaran_id' => 'integer',
        'master_status_pembayaran_id' => 'integer',

    ];

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function transaksiObject()
    {
        return $this->hasMany(TransaksiObjek::class, 'transaction_id', 'reference_no');
    }

    public function tipePembayaran()
    {
        return $this->belongsTo(MasterPayment::class, 'master_tipe_pembayaran_id');
    }

    public function statusPembayaran()
    {
        return $this->belongsTo(MasterPaymentStatus::class, 'master_status_pembayaran_id');
    }

    public function rating()
    {
        return $this->hasMany(Rating::class, 'transaksi_id');
    }

    public function document()
    {
        return $this->hasMany(Document::class, 'transaksi_id', 'reference_no');
    }

    public function installmentTransaction()
    {
        return $this->hasMany(InstallmentTransaction::class, 'transaction_id', 'reference_no');
    }
}
