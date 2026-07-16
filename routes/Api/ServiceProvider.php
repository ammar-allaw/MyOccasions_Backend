<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(ServiceProviderController::class)->prefix('service-provider')
->group(function(){
        Route::post('/manage-image/{userId?}','manageImageForServiceProvider')->name('/manage-image-for-service-provider')
        ->middleware(['auth.provider.or.owner']);

        Route::get('/get-details-of-service-provider/{userId?}','getServiceProviderDetails')->name('/get-details-of-service-provider')
                // ->middleware(['auth.provider.or.owner']);
        ->middleware(['auth.api.or.owner']);

        Route::put('/update-service-provider/{serviceProviderId?}','updateServiceProvider')->name('update-service-provider')
        ->middleware(['auth.provider.or.owner']);

        Route::delete('/force-delete/{serviceProviderId}','forceDeleteServiceProvider')->name('force-delete-service-provider')
        ->middleware(['auth:owner']);

        Route::get('/get-trashed','getServiceProvidersWithTrashed')->name('get-service-providers-with-trashed')
        ->middleware(['auth:owner']);

        Route::patch('/restore/{serviceProviderId}','restoreServiceProvider')->name('restore-service-provider')
        ->middleware(['auth:owner']);

        Route::delete('/soft-delete/{serviceProviderId}','softDeleteServiceProvider')->name('soft-delete-service-provider')
        ->middleware(['auth:owner']);
});

Route::controller(ServiceProviderController::class)->prefix('owner')->middleware('auth:owner')->group(function () {
    Route::get('/get-service-providers/{roleId?}', 'getServiceProvidersByRoleIdForOwner')->name('get-service-providers');
});

Route::post('/owner/add-service-provider', [ServiceProviderController::class, 'addServiceProvider'])
    ->name('add-service-provider')
    ->middleware(['auth:owner']);

Route::get('/app/get-service-providers-by-role-id/{roleId}', [ServiceProviderController::class, 'getServiceProvidersByRoleId'])
    ->name('get-service-providers-by-role-id')
    ->middleware(['auth:api']);
