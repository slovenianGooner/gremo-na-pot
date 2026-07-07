@extends('layouts.app')

@section('title', __('Edit Route'))

@section('content')
    <div class="app-header">
        <h1>🛠 {{ __('Edit Route') }}</h1>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="status-banner" style="background:#ffb3b3;">{{ session('error') }}</div>
    @endif

    <form action="{{ route('admin.routes.update', $route) }}" method="POST" class="admin-form">
        @csrf
        @method('PUT')
        <label for="name">{{ __('Route name') }}</label>
        <input type="text" id="name" name="name" value="{{ old('name', $route->name) }}" required>
        @error('name') <div style="color:#ffb3b3;">{{ $message }}</div> @enderror

        <button type="submit" class="btn btn-primary" style="flex:none; align-self:flex-start; padding:0.6rem 1.2rem;">{{ __('Save Name') }}</button>
    </form>

    <div class="places-manager">
        <h2 style="font-size:1.1rem;">{{ __('Suggest places from the map') }}</h2>
        <p style="opacity:0.75; font-size:0.9rem; margin-top:-0.25rem;">
            {{ __("Enter a start and end point and we'll look up towns along the driving route using OpenStreetMap. You'll get to pick which ones to add.") }}
        </p>
        <form action="{{ route('admin.routes.suggestions.preview', $route) }}" method="POST" class="admin-form" onsubmit="const b=this.querySelector('button[type=submit]'); b.disabled=true; b.textContent=@js(__('Searching… (up to 20s)'));">
            @csrf
            <label for="start_name">{{ __('Start point') }}</label>
            <input type="text" id="start_name" name="start_name" value="{{ old('start_name', $route->start_name) }}" placeholder="{{ __('e.g. Ljubljana, Slovenia') }}" required>

            <label for="end_name">{{ __('End point') }}</label>
            <input type="text" id="end_name" name="end_name" value="{{ old('end_name', $route->end_name) }}" placeholder="{{ __('e.g. Split, Croatia') }}" required>

            <button type="submit" class="btn btn-primary" style="flex:none; align-self:flex-start; padding:0.6rem 1.2rem;">{{ __('Find Places Along Route') }}</button>
        </form>
    </div>

    <div class="places-manager">
        <h2 style="font-size:1.1rem;">{{ __('Places (in order)') }}</h2>

        <div id="places-list">
            @foreach ($route->places as $place)
                @include('admin.routes.partials.place-row', ['place' => $place])
            @endforeach
        </div>

        <p id="no-places-msg" style="opacity:0.7;" @if ($route->places->isNotEmpty()) hidden @endif>
            {{ __('No places yet — add the first one below.') }}
        </p>

        <form id="add-place-form" action="{{ route('admin.places.store', $route) }}" method="POST" class="place-row" style="margin-top:1rem;">
            @csrf
            <input type="text" name="name" id="new-place-name" placeholder="{{ __('New place name') }}" required autocomplete="off">
            <button type="submit" class="btn btn-primary" style="flex:none; padding:0.5rem 1rem;">{{ __('Add Place') }}</button>
        </form>
        <p id="add-place-error" style="color:#ffb3b3; display:none;"></p>
    </div>

    <a href="{{ route('admin.routes.index') }}" class="back-link">← {{ __('Back to routes') }}</a>

    <script id="place-row-template" type="text/x-template">
        @include('admin.routes.partials.place-row', ['place' => null])
    </script>

    <script>
        (function () {
            const form = document.getElementById('add-place-form');
            const input = document.getElementById('new-place-name');
            const list = document.getElementById('places-list');
            const noPlacesMsg = document.getElementById('no-places-msg');
            const errorMsg = document.getElementById('add-place-error');
            const template = document.getElementById('place-row-template').textContent;

            function buildRow(place, updateUrl, destroyUrl) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template
                    .replaceAll('__ORDER__', place.order)
                    .replaceAll('__NAME__', place.name.replace(/"/g, '&quot;'))
                    .replaceAll('__UPDATE_URL__', updateUrl)
                    .replaceAll('__DESTROY_URL__', destroyUrl);
                return wrapper.firstElementChild;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                errorMsg.style.display = 'none';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' },
                })
                    .then(async (res) => {
                        if (!res.ok) {
                            const data = await res.json().catch(() => null);
                            throw new Error(data?.message || 'Request failed');
                        }
                        return res.json();
                    })
                    .then((data) => {
                        noPlacesMsg.hidden = true;
                        list.appendChild(buildRow(data.place, data.updateUrl, data.destroyUrl));
                        input.value = '';
                        input.focus();
                    })
                    .catch(() => {
                        errorMsg.textContent = @js(__('Could not add that place — please try again.'));
                        errorMsg.style.display = 'block';
                    });
            });
        })();
    </script>
@endsection
