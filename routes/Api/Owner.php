<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(OwnerController::class)->prefix('owner')
->group(function(){
        Route::post('/add-service-provider','addServiceProvider')->name('add-service-provider')
        ->middleware(['auth:owner']);

        Route::get('/get-roles-for-owner','getRolesForOwner')->name('get-roles-for-owner')
        ->middleware(['auth:owner']);

        Route::get('/get-service-providers/{roleId?}','getServiceProvidersByRoleIdForOwner')->name('get-service-providers')
        ->middleware(['auth:owner']);
        
        //not used now by ammar
        Route::get('/get-all-rooms-with-status','getAllRoomsWithStatus')->name('get-all-rooms-with-status')
        ->middleware(['auth:owner']);

        Route::delete('/force-delete-service-provider/{serviceProviderId}','forceDeleteServiceProvider')->name('force-delete-service-provider')
        ->middleware(['auth:owner']);

        Route::get('/get-service-providers-with-trashed','getServiceProvidersWithTrashed')->name('get-service-providers-with-trashed')
        ->middleware(['auth:owner']);

        Route::delete('/soft-delete-service-provider/{serviceProviderId}','softDeleteServiceProvider')->name('soft-delete-service-provider')
        ->middleware(['auth:owner']);

        // Route::put('/update-room-status/{roomId}','updateRoomStatus')->name('update-room-status')
        // ->middleware(['auth:owner']);
});