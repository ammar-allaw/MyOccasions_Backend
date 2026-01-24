<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(ServiceProviderController::class)->prefix('service-provider')
->group(function(){
        Route::post('/add-image-for-service-provider/{userId?}','addImageForServiceProvider')->name('/add-image-for-service-provider')
        ->middleware(['auth.provider.or.owner']);

        Route::put('/update-service-provider/{serviceProviderId?}','updateServiceProvider')->name('update-service-provider')
        ->middleware(['auth.provider.or.owner']);
});

