<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentTransaction extends Model
{
    use HasFactory;

    protected $table = 'installment_transactions';

    protected $fillable = ['installment_ref_id', 'installment_old_ref_id', 'installment_total', 'installment_index', 'transaction_id', 'payment_amount', 'penalty_amount', 'grand_total', 'master_tipe_pembayaran_id', 'next_penalty_date', 'master_status_pembayaran_id', 'start_date', 'end_date', 'due_date', 'paid_date', 'qr_url', 'va', 'kode_bayar'];

    protected $casts = [
        'installment_total' => 'integer',
        'installment_index' => 'integer',
        'payment_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'master_tipe_pembayaran_id' => 'integer',
        'master_status_pembayaran_id' => 'integer',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'reference_no');
    }

    public function statusPembayaran()
    {
        return $this->belongsTo(MasterPaymentStatus::class, 'master_status_pembayaran_id');
    }

    public function tipePembayaran()
    {
        return $this->belongsTo(MasterPayment::class, 'master_tipe_pembayaran_id');
    }
}
