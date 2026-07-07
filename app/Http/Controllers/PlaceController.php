<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\TravelRoute;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function store(Request $request, TravelRoute $route)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $nextOrder = ($route->places()->max('order') ?? 0) + 1;

        $place = $route->places()->create([
            'name' => $validated['name'],
            'order' => $nextOrder,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'place' => $place,
                'updateUrl' => route('admin.places.update', $place),
                'destroyUrl' => route('admin.places.destroy', $place),
            ]);
        }

        return redirect()->route('admin.routes.edit', $route)->with('status', __('Place added.'));
    }

    public function update(Request $request, Place $place)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $place->update($validated);

        return redirect()->route('admin.routes.edit', $place->travel_route_id)->with('status', __('Place updated.'));
    }

    public function destroy(Place $place)
    {
        $routeId = $place->travel_route_id;
        $place->delete();

        return redirect()->route('admin.routes.edit', $routeId)->with('status', __('Place removed.'));
    }
}
