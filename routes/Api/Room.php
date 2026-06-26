<?php

use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::controller(RoomController::class)->prefix('hall')
->group(function () {
    Route::post('/add-room/{serviceProviderId?}', 'addRoom')->name('add-room')
        ->middleware('can.access:true,false,add_room');

    Route::get('get-room', 'getRoom')->name('get-room')
        ->middleware(['auth:api']);

    Route::get('get-rooms-by-hall-id/{hallId}', 'getRoomsByHallId')->name('get-rooms-by-hall-id')
        ->middleware(['auth:api']);

    Route::post('update-room/{roomId}', 'updateRoom')->name('update-room')
        ->middleware('can.access:true,false,update_room');

    Route::get('/get-details-of-hall/{hallId?}', 'getDetailsOfHall')->name('get-details-of-hall')
        ->middleware(['auth.api.or.owner']);

    Route::delete('/delete-room/{roomId}', 'deleteRoom')->name('delete-room')
        ->middleware('can.access:true,false,delete_room');
});
