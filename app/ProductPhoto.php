<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPhoto extends Model
{
    protected $fillable = [
        'photo_name', 'photo_url',
    ];

    public function products()
    {
        return $this->belongsTo('App\Product');
    }
}
