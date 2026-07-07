@extends('layouts.app')

@section('title', __('Choose a Route'))

@section('content')
    <div class="app-header">
        <h1>🚗 {{ __('Where Are We?') }}</h1>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if ($routes->isEmpty())
        <div class="empty-state">
            {{ __('No routes yet.') }}<br>
            <a href="{{ route('admin.routes.create') }}" style="color:#ffd100;">{{ __('Create your first route') }}</a>
        </div>
    @else
        <div class="route-list">
            @foreach ($routes as $route)
                <a href="{{ route('play.show', $route) }}" class="route-card">
                    <span class="route-name">{{ $route->name }}</span>
                    <span class="route-meta">{{ trans_choice('messages.places_count', $route->places_count) }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <a href="{{ route('admin.routes.index') }}" class="back-link">{{ __('Manage routes (admin)') }}</a>
@endsection
