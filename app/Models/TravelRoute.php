<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelRoute extends Model
{
    protected $fillable = [
        'name', 'slug',
        'start_name', 'start_lat', 'start_lng',
        'end_name', 'end_lat', 'end_lng',
    ];

    public function places()
    {
        return $this->hasMany(Place::class)->orderBy('order');
    }
}
