<?php

namespace App\Http\Controllers;

use App\Models\TravelRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TravelRouteController extends Controller
{
    public function index()
    {
        $routes = TravelRoute::withCount('places')->orderBy('name')->get();

        return view('admin.routes.index', compact('routes'));
    }

    public function create()
    {
        return view('admin.routes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['name']);
        $original = $slug;
        $i = 1;
        while (TravelRoute::where('slug', $slug)->exists()) {
            $slug = "{$original}-" . $i++;
        }

        $route = TravelRoute::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.routes.edit', $route)->with('status', __('Route created. Now add some places!'));
    }

    public function edit(TravelRoute $route)
    {
        $route->load('places');

        return view('admin.routes.edit', compact('route'));
    }

    public function update(Request $request, TravelRoute $route)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $route->update($validated);

        return redirect()->route('admin.routes.edit', $route)->with('status', __('Route updated.'));
    }

    public function destroy(TravelRoute $route)
    {
        $route->delete();

        return redirect()->route('admin.routes.index')->with('status', __('Route deleted.'));
    }
}
