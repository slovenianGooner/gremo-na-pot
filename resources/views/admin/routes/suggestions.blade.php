@extends('layouts.app')

@section('title', __('Suggested Places'))

@section('content')
    <div class="app-header">
        <h1>🗺 {{ __('Suggested Places') }}</h1>
    </div>

    <p style="opacity:0.8; text-align:center;">
        {{ __('For :route — untick anything you don\'t want, then add the rest.', ['route' => $route->name]) }}
    </p>

    @if ($suggestions->isEmpty())
        <div class="empty-state">
            {{ __('No places were found along that route. Try different start/end points, or add places manually.') }}
        </div>
    @else
        <form action="{{ route('admin.routes.suggestions.confirm', $route) }}" method="POST" class="admin-form">
            @csrf

            @foreach ($suggestions as $index => $place)
                <label class="place-row" style="cursor:pointer;">
                    <input type="checkbox" name="selected[{{ $index }}]" value="1" checked style="width:auto; flex:none;">
                    <span style="flex:1;">
                        {{ $place['name'] }}
                        @if ($index === 0)
                            <span style="opacity:0.6; font-size:0.85rem;"> ({{ __('start') }})</span>
                        @elseif ($index === $suggestions->count() - 1)
                            <span style="opacity:0.6; font-size:0.85rem;"> ({{ __('end') }})</span>
                        @endif
                    </span>
                    <input type="hidden" name="places[{{ $index }}][name]" value="{{ $place['name'] }}">
                    <input type="hidden" name="places[{{ $index }}][lat]" value="{{ $place['lat'] }}">
                    <input type="hidden" name="places[{{ $index }}][lng]" value="{{ $place['lng'] }}">
                </label>
            @endforeach

            <button type="submit" class="btn btn-primary" style="margin-top:1rem;">{{ __('Add Selected Places') }}</button>
        </form>
    @endif

    <a href="{{ route('admin.routes.edit', $route) }}" class="back-link">← {{ __('Back to route') }}</a>
@endsection
