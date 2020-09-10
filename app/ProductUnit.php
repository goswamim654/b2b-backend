<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'units', 'mrp', 'rate', 'moq','available', 'stock'
    ];

    public function products()
    {
        return $this->belongsTo('App\Product');
    }
}
