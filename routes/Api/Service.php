<?php

use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::controller(ServiceController::class)->prefix('service')
->group(function () {
    Route::get('/get-service-for-service-provider/{serviceProviderId?}', 'getServiceForServiceProvider')
        ->name('get-services-for-service-provider')
        ->middleware('can.access:true,true,get_services');

    Route::get('/get-details-of-service/{serviceId}', 'getDetailsOfService')
        ->name('get-details-of-service')
        ->middleware(['auth.api.or.owner']);

    Route::post('/add-main-key', 'addMainKey')
        ->name('add-main-key')->middleware(['auth:owner']);

    Route::get('/get-main-keys', 'getMainKeys')
        ->name('get-main-keys')->middleware(['auth.api.or.owner']);

    Route::put('/update-main-key/{id}', 'updateMainKey')
        ->name('update-main-key')->middleware(['auth:owner']);

    Route::delete('/delete-main-key/{id}', 'deleteMainKey')
        ->name('delete-main-key')->middleware(['auth:owner']);
});

Route::controller(ServiceController::class)->prefix('hall')
->group(function () {
    Route::post('/add-service/{serviceProviderId?}', 'addService')->name('add-service')
        ->middleware('can.access:true,false,add_service');

    Route::post('/update-service/{serviceId}', 'updateService')->name('update-service')
        ->middleware('can.access:true,false,update_service');

    Route::delete('/delete-service/{serviceId}', 'deleteService')->name('delete-service')
        ->middleware('can.access:true,false,delete_service');
});
