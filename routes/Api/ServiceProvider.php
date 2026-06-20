<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(ServiceProviderController::class)->prefix('service-provider')
->group(function(){
        Route::post('/add-image-for-service-provider/{userId?}','addImageForServiceProvider')->name('/add-image-for-service-provider')
        ->middleware(['auth.provider.or.owner']);

        Route::get('/get-details-of-service-provider/{userId?}','getServiceProviderDetails')->name('/get-details-of-service-provider')
                // ->middleware(['auth.provider.or.owner']);
        ->middleware(['auth.api.or.owner']);

        Route::put('/update-service-provider/{serviceProviderId?}','updateServiceProvider')->name('update-service-provider')
        ->middleware(['auth.provider.or.owner']);
});

Route::post('/owner/add-service-provider', [ServiceProviderController::class, 'addServiceProvider'])
    ->name('add-service-provider')
    ->middleware(['auth:owner']);

Route::get('/app/get-service-providers-by-role-id/{roleId}', [ServiceProviderController::class, 'getServiceProvidersByRoleId'])
    ->name('get-service-providers-by-role-id')
    ->middleware(['auth:api']);