<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterRole extends Model
{
    protected $table = 'master_role';

    protected $fillable = ['nama_role'];

    public function users()
    {
        return $this->hasMany(User::class, 'master_role_id');
    }

    public function chief()
    {
        return $this->hasMany(
            MasterChief::class,
            'master_role_id'
        );
    }
}
