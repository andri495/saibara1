<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    protected $table = 'cart';
    protected $with = ['subject', 'cartObject'];

    protected $fillable = [
        'user_id',
        'object_opd_id',
        'object_retribusi_id',
    ];


    public function subject()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function firstObject()
    {
        // Get the first object from objects_id array
        return $this->belongsTo(
            MasterObjek::class,
            DB::raw('JSON_EXTRACT(objects_id, "$[0]")'),
            'id'
        );
    }

    public function objects()
    {
        return $this->belongsToMany(
            MasterObjek::class,
            null, // No pivot table since we use JSON
            'cart_id',
            'id'
        )->whereIn('master_objek.id', $this->objects_id ?? []);
    }

    public function cartObject()
    {
        return $this->hasMany(CartObject::class, 'cart_id');
    }
}
