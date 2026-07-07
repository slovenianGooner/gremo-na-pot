<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RouteSuggestionService
{
    protected string $userAgent = 'RouteGameApp/1.0 (personal family trip game)';

    /**
     * Look up a single place by free-text name and return its coordinates.
     */
    public function geocode(string $query): array
    {
        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);

        if (! $response->ok() || empty($response->json())) {
            throw new RuntimeException(__('Could not find a place matching ":query".', ['query' => $query]));
        }

        $result = $response->json()[0];

        return [
            'name' => $this->shortName($result['display_name']),
            'lat' => (float) $result['lat'],
            'lng' => (float) $result['lon'],
        ];
    }

    /**
     * Build an ordered list of suggested places between two geocoded endpoints,
     * by sampling the driving route and finding the nearest town to each sample.
     *
     * All Overpass lookups for a batch of samples run concurrently (in small
     * chunks) rather than one-at-a-time, which is what made this slow before.
     */
    public function suggestWaypoints(array $start, array $end, int $maxWaypoints = 8): array
    {
        $route = $this->fetchRoute($start, $end);

        // A modest buffer over what we need — some samples (mountains, sparse
        // rural stretches) won't turn up a nearby town at all.
        $rawSampleCount = min($maxWaypoints * 2, 16);
        $samples = $this->sampleAlongRoute($route['coordinates'], $route['distance'], $rawSampleCount);

        $results = $this->queryOverpassBatch($samples, 25000);

        $missingIndexes = array_keys(array_filter($results, fn ($r) => $r === null));
        if (! empty($missingIndexes)) {
            $retrySamples = array_intersect_key($samples, array_flip($missingIndexes));
            $retryResults = $this->queryOverpassBatch($retrySamples, 60000);
            $results = $retryResults + $results;
        }

        ksort($results);

        $suggestions = [];
        $seenNames = [strtolower($start['name'])];

        foreach ($results as $place) {
            if (! $place || count($suggestions) >= $maxWaypoints) {
                continue;
            }

            $key = strtolower($place['name']);
            if (in_array($key, $seenNames, true)) {
                continue;
            }

            $seenNames[] = $key;
            $suggestions[] = $place;
        }

        return $suggestions;
    }

    protected function fetchRoute(array $start, array $end): array
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F',
            $start['lng'], $start['lat'], $end['lng'], $end['lat']
        );

        $response = Http::timeout(20)->get($url, [
            'overview' => 'full',
            'geometries' => 'geojson',
        ]);

        if (! $response->ok() || ($response->json('code') !== 'Ok')) {
            throw new RuntimeException(__('Could not calculate a driving route between those two places.'));
        }

        $route = $response->json('routes.0');

        return [
            // OSRM returns [lng, lat] pairs; flip to [lat, lng] for our own use.
            'coordinates' => array_map(
                fn ($pair) => ['lat' => $pair[1], 'lng' => $pair[0]],
                $route['geometry']['coordinates']
            ),
            'distance' => (float) $route['distance'],
        ];
    }

    /**
     * Pick evenly spaced points along the route polyline (excluding the very
     * start and end, since those are already known).
     */
    protected function sampleAlongRoute(array $coordinates, float $totalDistanceMeters, int $maxWaypoints): array
    {
        if (count($coordinates) < 2 || $totalDistanceMeters <= 0) {
            return [];
        }

        $interval = $totalDistanceMeters / ($maxWaypoints + 1);
        $targets = [];
        for ($i = 1; $i <= $maxWaypoints; $i++) {
            $targets[] = $interval * $i;
        }

        $samples = [];
        $cumulative = 0.0;
        $targetIndex = 0;

        for ($i = 1; $i < count($coordinates) && $targetIndex < count($targets); $i++) {
            $segmentLength = $this->haversineMeters($coordinates[$i - 1], $coordinates[$i]);
            $cumulative += $segmentLength;

            while ($targetIndex < count($targets) && $cumulative >= $targets[$targetIndex]) {
                $samples[] = $coordinates[$i];
                $targetIndex++;
            }
        }

        return $samples;
    }

    /**
     * Look up the most notable town/city near each of several points,
     * firing the Overpass requests concurrently in small chunks (a public
     * instance will throttle/queue us if we open too many connections at
     * once, so we don't send them all in a single burst).
     *
     * @param  array<int, array{lat: float, lng: float}>  $points  keyed by original sample index
     * @return array<int, array|null> same keys as $points
     */
    protected function queryOverpassBatch(array $points, int $radius, int $concurrency = 4): array
    {
        $results = [];

        foreach (array_chunk($points, $concurrency, true) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk, $radius) {
                foreach ($chunk as $index => $point) {
                    $query = "[out:json][timeout:15];node(around:{$radius},{$point['lat']},{$point['lng']})[place~\"^(city|town|village)$\"];out body 30;";

                    $pool->as((string) $index)
                        ->withHeaders(['User-Agent' => $this->userAgent])
                        ->timeout(18)
                        ->asForm()
                        ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);
                }
            });

            foreach ($chunk as $index => $point) {
                $response = $responses[(string) $index] ?? null;
                $results[$index] = $this->extractNearestPlace($response, $point);
            }
        }

        return $results;
    }

    protected function extractNearestPlace($response, array $point): ?array
    {
        if (! $response || $response instanceof \Throwable || ! $response->ok()) {
            return null;
        }

        $elements = $response->json('elements') ?? [];
        if (empty($elements)) {
            return null;
        }

        $typeRank = ['city' => 3, 'town' => 2, 'village' => 1];

        return collect($elements)
            ->map(function ($el) use ($point, $typeRank) {
                return [
                    'name' => $el['tags']['name'] ?? null,
                    'lat' => (float) $el['lat'],
                    'lng' => (float) $el['lon'],
                    'population' => (int) ($el['tags']['population'] ?? 0),
                    'type_rank' => $typeRank[$el['tags']['place'] ?? ''] ?? 0,
                    'distance' => $this->haversineMeters(
                        $point,
                        ['lat' => (float) $el['lat'], 'lng' => (float) $el['lon']]
                    ),
                ];
            })
            ->filter(fn ($p) => ! empty($p['name']))
            ->sortBy([
                ['type_rank', 'desc'],
                ['population', 'desc'],
                ['distance', 'asc'],
            ])
            ->first();
    }

    protected function haversineMeters(array $a, array $b): float
    {
        $earthRadius = 6371000;

        $latDelta = deg2rad($b['lat'] - $a['lat']);
        $lngDelta = deg2rad($b['lng'] - $a['lng']);

        $h = sin($latDelta / 2) ** 2
            + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($h)));
    }

    /**
     * Nominatim returns a full "City, Municipality, Region, Country" style
     * string; take just the first, most human-friendly segment.
     */
    protected function shortName(string $displayName): string
    {
        return trim(explode(',', $displayName)[0]);
    }
}
