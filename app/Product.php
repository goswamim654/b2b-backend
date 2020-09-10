<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category', 'sub_category', 'name', 'description', 'code', 'tax'
    ];

    public function photos()
    {
        return $this->hasMany('App\ProductPhoto');
    }

    public function units()
    {
        return $this->hasMany('App\ProductUnit');
    }
}

