<?php

use App\Http\Controllers\Api\FoodController;
use Illuminate\Support\Facades\Route;

Route::controller(FoodController::class)
    ->prefix('food')
    ->group(function () {
        Route::post('/add-food/{serviceProviderId?}', 'addFood')
            ->name('add-food')
            ->middleware('can.access:true,false,add_food');

        Route::put('/update-food/{foodId}', 'updateFood')
            ->name('update-food')
            ->middleware('can.access:true,false,update_food');

        Route::get('/get-foods', 'getFoods')
            ->name('get-foods')
            ->middleware('can.access:true,true,get_foods');

        Route::get('/get-food/{foodId}', 'getFood')
            ->name('get-food')
            ->middleware('can.access:true,true,find_food');
    });
