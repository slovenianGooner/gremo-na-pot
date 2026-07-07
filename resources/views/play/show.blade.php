@extends('layouts.app')

@section('title', $route->name)

@section('content')
    <div class="app-header">
        <h1><a href="{{ route('play.home') }}">🚗 {{ $route->name }}</a></h1>
    </div>

    @if ($route->places->isEmpty())
        <div class="empty-state">
            {{ __('This route has no places yet.') }}<br>
            <a href="{{ route('admin.routes.edit', $route) }}" style="color:#ffd100;">{{ __('Add some in admin') }}</a>
        </div>
    @else
        <div id="game" data-storage-key="route-game-progress-{{ $route->slug }}"></div>

        <script id="places-data" type="application/json">
            {!! $route->places->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->toJson() !!}
        </script>

        <script id="i18n-data" type="application/json">
            {!! json_encode([
                'nowApproaching' => __('Now Approaching'),
                'placeOf' => __('Place :current of :total'),
                'back' => __('Back'),
                'nextHere' => __("We're here! Next"),
                'youMadeIt' => __('You made it to'),
                'playAgain' => __('Play Again'),
            ]) !!}
        </script>

        <script>
            (function () {
                const places = JSON.parse(document.getElementById('places-data').textContent);
                const i18n = JSON.parse(document.getElementById('i18n-data').textContent);
                const game = document.getElementById('game');
                const storageKey = game.dataset.storageKey;

                let step = parseInt(localStorage.getItem(storageKey) || '0', 10);
                if (isNaN(step) || step < 0 || step >= places.length) step = 0;

                function save() {
                    localStorage.setItem(storageKey, String(step));
                }

                function renderProgress() {
                    return places.map((_, i) => {
                        const cls = i < step ? 'is-done' : (i === step ? 'is-current' : '');
                        return `<span class="progress-dot ${cls}"></span>`;
                    }).join('');
                }

                function placeOfLabel(current, total) {
                    return i18n.placeOf.replace(':current', current).replace(':total', total);
                }

                function render() {
                    if (step >= places.length) {
                        game.innerHTML = `
                            <div class="finish-screen">
                                <div class="emoji">🏁</div>
                                <h2>${i18n.youMadeIt}<br>${escapeHtml(places[places.length - 1].name)}!</h2>
                                <div class="controls">
                                    <button class="btn btn-primary" id="restart-btn">${i18n.playAgain}</button>
                                </div>
                            </div>
                        `;
                        document.getElementById('restart-btn').addEventListener('click', () => {
                            step = 0;
                            save();
                            render();
                        });
                        return;
                    }

                    const place = places[step];
                    game.innerHTML = `
                        <div class="sign-stage">
                            <div class="progress-track">${renderProgress()}</div>
                            <div class="road-sign">
                                <div class="sign-label">${i18n.nowApproaching}</div>
                                <div class="sign-place-name">${escapeHtml(place.name)}</div>
                                <div class="sign-arrow">➜</div>
                            </div>
                            <div class="step-counter">${placeOfLabel(step + 1, places.length)}</div>
                            <div class="controls">
                                <button class="btn btn-secondary" id="back-btn" ${step === 0 ? 'disabled' : ''}>◀ ${i18n.back}</button>
                                <button class="btn btn-primary" id="next-btn">${i18n.nextHere} ▶</button>
                            </div>
                        </div>
                    `;

                    document.getElementById('next-btn').addEventListener('click', () => {
                        step++;
                        save();
                        render();
                    });

                    const backBtn = document.getElementById('back-btn');
                    if (!backBtn.disabled) {
                        backBtn.addEventListener('click', () => {
                            step = Math.max(0, step - 1);
                            save();
                            render();
                        });
                    }
                }

                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str;
                    return div.innerHTML;
                }

                render();
            })();
        </script>
    @endif

    <a href="{{ route('play.home') }}" class="back-link">{{ __('Choose a different route') }}</a>
@endsection
