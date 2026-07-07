@extends('layouts.app')

@section('title', __('Manage Routes'))

@section('content')
    <div class="app-header">
        <h1>🛠 {{ __('Manage Routes') }}</h1>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    <div class="top-actions">
        <a href="{{ route('play.home') }}" class="back-link" style="margin:0;">← {{ __('Back to game') }}</a>
        <a href="{{ route('admin.routes.create') }}" class="btn btn-primary" style="flex:none; padding:0.6rem 1rem;">+ {{ __('New Route') }}</a>
    </div>

    @if ($routes->isEmpty())
        <div class="empty-state">{{ __('No routes yet. Create one to get started.') }}</div>
    @else
        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Places') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($routes as $route)
                    <tr>
                        <td>{{ $route->name }}</td>
                        <td>{{ $route->places_count }}</td>
                        <td>
                            <a href="{{ route('admin.routes.edit', $route) }}" class="link-btn">{{ __('Edit') }}</a>
                            &nbsp;·&nbsp;
                            <form action="{{ route('admin.routes.destroy', $route) }}" method="POST" class="inline-form" onsubmit="return confirm('{{ __('Delete this route and all its places?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-btn">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
