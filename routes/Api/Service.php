<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(ServiceController::class)->prefix('service')
->group(function(){
        Route::get('/get-service-for-service-provider/{serviceProviderId?}','getServiceForServiceProvider')
        ->name('get-services-for-service-provider')
        ->middleware(['auth.api.or.owner']);
        Route::get('/get-details-of-service/{serviceId}','getDetailsOfService')
        ->name('get-details-of-service')
        ->middleware(['auth.api.or.owner']);
});


