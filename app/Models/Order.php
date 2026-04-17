<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'shop_id',
        'billing_address_id',
        'shipping_address_id',
        'reference',
        'amount',
        'currency',
        'status',
        'payment_method',
        'external_transaction_id',
        'api_response_log',
        'notes'
    ];

    protected $casts = [
        'api_response_log' => 'array',
        'amount' => 'float'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            // Si l'ID n'est pas déjà défini, on génère un UUID
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }
}
