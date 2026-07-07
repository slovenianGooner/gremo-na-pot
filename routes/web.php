<?php

use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\RouteSuggestionController;
use App\Http\Controllers\TravelRouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlayController::class, 'home'])->name('play.home');
Route::get('/play/{route:slug}', [PlayController::class, 'show'])->name('play.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('routes', TravelRouteController::class)->parameters(['routes' => 'route'])->except('show');
    Route::post('routes/{route}/places', [PlaceController::class, 'store'])->name('places.store');
    Route::put('places/{place}', [PlaceController::class, 'update'])->name('places.update');
    Route::delete('places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');

    Route::post('routes/{route}/suggestions', [RouteSuggestionController::class, 'preview'])->name('routes.suggestions.preview');
    Route::post('routes/{route}/suggestions/confirm', [RouteSuggestionController::class, 'confirm'])->name('routes.suggestions.confirm');
});
