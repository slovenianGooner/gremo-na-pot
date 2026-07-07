<?php

namespace App\Http\Controllers;

use App\Models\TravelRoute;
use App\Services\RouteSuggestionService;
use Illuminate\Http\Request;
use RuntimeException;

class RouteSuggestionController extends Controller
{
    public function preview(Request $request, TravelRoute $route, RouteSuggestionService $service)
    {
        // Geocoding + OSRM + several Overpass calls can take longer than the
        // default 30s execution limit; this is a one-off admin action, not a
        // hot path, so a generous ceiling here is fine.
        set_time_limit(120);

        $validated = $request->validate([
            'start_name' => ['required', 'string', 'max:255'],
            'end_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $start = $service->geocode($validated['start_name']);
            $end = $service->geocode($validated['end_name']);
            $waypoints = $service->suggestWaypoints($start, $end);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.routes.edit', $route)->with('error', $e->getMessage());
        }

        $route->update([
            'start_name' => $start['name'],
            'start_lat' => $start['lat'],
            'start_lng' => $start['lng'],
            'end_name' => $end['name'],
            'end_lat' => $end['lat'],
            'end_lng' => $end['lng'],
        ]);

        $suggestions = collect([$start, ...$waypoints, $end])->values();

        return view('admin.routes.suggestions', compact('route', 'suggestions'));
    }

    public function confirm(Request $request, TravelRoute $route)
    {
        $validated = $request->validate([
            'places' => ['required', 'array'],
            'places.*.name' => ['required', 'string', 'max:255'],
            'places.*.lat' => ['nullable', 'numeric'],
            'places.*.lng' => ['nullable', 'numeric'],
            'selected' => ['array'],
            'selected.*' => ['string'],
        ]);

        $selectedIndexes = array_keys($validated['selected'] ?? []);
        $nextOrder = ($route->places()->max('order') ?? -1) + 1;

        foreach ($selectedIndexes as $index) {
            $place = $validated['places'][$index] ?? null;
            if (! $place) {
                continue;
            }

            $route->places()->create([
                'name' => $place['name'],
                'lat' => $place['lat'] ?? null,
                'lng' => $place['lng'] ?? null,
                'order' => $nextOrder++,
            ]);
        }

        $count = count($selectedIndexes);

        return redirect()->route('admin.routes.edit', $route)
            ->with('status', $count > 0 ? trans_choice('messages.added_places', $count) : __('No places were selected.'));
    }
}
