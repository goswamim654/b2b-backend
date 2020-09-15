<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PreviewProductPhoto extends Model
{
    protected $fillable = [
        'photo_name', 'photo_url',
    ];

    public function products()
    {
        return $this->belongsTo('App\PreviewProduct');
    }
}
