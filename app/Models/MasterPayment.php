<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPayment extends Model
{
    protected $table = 'master_payment';
    protected $fillable = ['tipe_pembayaran'];
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'master_tipe_pembayaran_id');
    }
    public function installment()
    {
        return $this->hasMany(InstallmentTransaction::class, 'master_tipe_pembayaran_id');
    }
    public function document()
    {
        return $this->hasMany(Document::class, 'master_tipe_pembayaran_id');
    }
}
