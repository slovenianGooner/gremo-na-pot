<?php

namespace App\Http\Controllers;

use App\Models\TravelRoute;

class PlayController extends Controller
{
    public function home()
    {
        $routes = TravelRoute::withCount('places')->orderBy('name')->get();

        return view('play.home', compact('routes'));
    }

    public function show(TravelRoute $route)
    {
        $route->load('places');

        return view('play.show', compact('route'));
    }
}
