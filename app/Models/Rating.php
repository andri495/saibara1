<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'ratings';
    protected $fillable = ['rating_star', 'comment', 'master_objek_id', 'transaksi_id', 'user_id'];
    protected $with = ['user'];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }
    public function objek()
    {
        return $this->belongsTo(MasterObjek::class, 'master_objek_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
