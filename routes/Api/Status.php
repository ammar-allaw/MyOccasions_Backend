<?php

use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::controller(StatusController::class)
    ->prefix('status')
    ->middleware('auth.provider.or.owner')
    ->group(function () {
        Route::get('/', 'index')->name('statuses.index');
    });
