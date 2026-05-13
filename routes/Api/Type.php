<?php

use App\Http\Controllers\Api\TypeController;
use Illuminate\Support\Facades\Route;

Route::controller(TypeController::class)
    ->prefix('type')
    ->group(function () {
        Route::get('/', 'getTypes')
            ->name('get-types')
            ->middleware('can.access:true,true,get_types');

        Route::post('/', 'storeType')
            ->name('store-type')
            ->middleware('auth.owner');

        Route::put('/{type}', 'updateType')
            ->name('update-type')
            ->middleware('auth.owner');

        Route::delete('/{type}', 'destroyType')
            ->name('delete-type')
            ->middleware('auth.owner');
    });
