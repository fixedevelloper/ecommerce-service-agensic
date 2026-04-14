<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'phone',
        'street',
        'city',
        'postal_code',
        'country_code',
        'lat',
        'lng',
        'is_default'
    ];
}
