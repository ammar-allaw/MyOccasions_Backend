<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(HallController::class)->prefix('hall')
->group(function(){
        Route::post('/add-room/{serviceProviderId?}','addRoom')->name('add-room')
        // ->middleware(['auth.provider.or.owner:halls']);
        ->middleware('can.access:true,false,add_room');
        
        //not used 
        Route::get('get-room','getRoom')->name('get-room')
        ->middleware(['auth:api']);

        //not used 
        Route::get('get-rooms-by-hall-id/{hallId}','getRoomsByHallId')->name('get-rooms-by-hall-id')
        ->middleware(['auth:api']);

        Route::post('update-room/{roomId}','updateRoom')->name('update-room')
        // ->middleware(['auth.provider.or.owner:halls']);
        ->middleware('can.access:true,false,update_room');

        //for service 
        Route::post('/add-service/{serviceProviderId?}','addService')->name('add-service')
        // ->middleware(['auth.provider.or.owner:halls']);
        // ->middleware(['auth.provider.or.owner']);
        ->middleware('can.access:true,false,add_service');

        Route::get('/get-details-of-hall/{hallId?}','getDetailsOfHall')->name('get-details-of-hall')
        ->middleware(['auth.api.or.owner']);
        Route::post('/update-service/{serviceId}','updateService')->name('update-service')
        // ->middleware(['auth.provider.or.owner:halls']);
        // ->middleware(['auth.provider.or.owner']);
        ->middleware('can.access:true,false,update_service');

        Route::delete('/delete-service/{serviceId}','deleteService')->name('delete-service')
        // ->middleware(['auth.provider.or.owner']);
        ->middleware('can.access:true,false,delete_service');

        Route::delete('/delete-room/{roomId}','deleteRoom')->name('delete-room')
        // ->middleware(['auth.provider.or.owner:halls']);
        ->middleware('can.access:true,false,delete_room');

});


