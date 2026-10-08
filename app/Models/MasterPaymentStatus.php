<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPaymentStatus extends Model
{
    protected $table = 'master_payment_status';
    protected $fillable = ['status_pembayaran'];
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'master_status_pembayaran_id');
    }

    public function document()
    {
        return $this->hasMany(Document::class, 'master_status_pembayaran_id');
    }
}
