<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
        'price',
        'category',
        'sku',
        'stock',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'float'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function images()
    {
        return $this->belongsToMany(Image::class, 'images_products')
            ->withPivot(['position', 'is_primary']);
    }
}
