@extends('layouts.app')

@section('title', __('New Route'))

@section('content')
    <div class="app-header">
        <h1>🛠 {{ __('New Route') }}</h1>
    </div>

    <form action="{{ route('admin.routes.store') }}" method="POST" class="admin-form">
        @csrf
        <label for="name">{{ __('Route name') }}</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. Ljubljana to Split') }}" required autofocus>
        @error('name') <div style="color:#ffb3b3;">{{ $message }}</div> @enderror

        <button type="submit" class="btn btn-primary">{{ __('Create Route') }}</button>
    </form>

    <a href="{{ route('admin.routes.index') }}" class="back-link">← {{ __('Back to routes') }}</a>
@endsection
