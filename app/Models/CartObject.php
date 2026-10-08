<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartObject extends Model
{
    protected $table = 'cart_objects';

    protected $fillable = [
        'cart_id',
        'object_id',
        'quantity_tpj',
        'quantity_satuan',
        'total_amount',
        'document_result',
        'document_payment_link',
        'document_support_link',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    protected $with = ['object'];

    protected $casts = [
        'cart_id' => 'integer',
        'object_id' => 'integer',
        'quantity_tpj' => 'double',
        'quantity_satuan' => 'double',
        'total_amount' => 'decimal:2', // or 'decimal:2' for monetary values
    ];

    public function getObjectsDataAttribute()
    {
        if (empty($this->object_id)) {
            return collect();
        }

        return MasterObjek::whereIn('id', $this->object_id)->get();
    }

    public function cartObject()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function object()
    {
        return $this->belongsTo(MasterObjek::class, 'object_id');
    }
}
