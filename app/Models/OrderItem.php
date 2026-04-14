<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'sku',
        'price',
        'quantity',
        'total',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'float',
        'total' => 'float'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
