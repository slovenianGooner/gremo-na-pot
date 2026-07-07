<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = ['travel_route_id', 'name', 'order', 'lat', 'lng'];

    public function travelRoute()
    {
        return $this->belongsTo(TravelRoute::class);
    }
}
