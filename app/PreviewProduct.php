<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PreviewProduct extends Model
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
        return $this->hasMany('App\PreviewProductPhoto');
    }

    public function units()
    {
        return $this->hasMany('App\PreviewProductUnit');
    }
}

