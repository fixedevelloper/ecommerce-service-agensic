<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'alt',
        'user_id'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'images_products')
            ->withPivot(['position', 'is_primary'])
            ->withTimestamps();
    }
}
