<?php

use App\Http\Controllers\Api\InteractionController;
use Illuminate\Support\Facades\Route;

Route::controller(InteractionController::class)
    ->prefix('interactions')
    ->middleware(['auth:api', 'auth.client', 'throttle:interactions'])
    ->group(function () {
        Route::post('/{type}/{id}/view', 'view')->name('interactions.view');
        Route::post('/{type}/{id}/like', 'like')->name('interactions.like');
        Route::delete('/{type}/{id}/like', 'unlike')->name('interactions.unlike');
        Route::get('/{type}/{id}/stats', 'stats')->name('interactions.stats');
    });
