<?php

namespace Mo\Accept\Models;

use Illuminate\Database\Eloquent\Model;

class AcceptOrder extends Model
{
    protected $fillable = ['buyer_type', 'buyer_id', 'product_type', 'product_id', 'quantity', 'currency', 'amount_cents', 'order_id', 'is_3d', 'ref_code'];

    public function buyer()
    {
        return $this->morphTo('buyer');
    }

    public function product()
    {
        return $this->morphTo('product');
    }
}
