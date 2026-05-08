<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(AppController::class)->prefix('app')
->group(function(){
        Route::get('/get-roles','getRoles')->name('get-roles')
        ->middleware(['auth:api']);

        Route::get('/get-types','getTypes')->name('get-types')
        ->middleware(['auth:api']);

        Route::get('/get-service-providers-by-role-id/{roleId}','getServiceProvidersByRoleId')->name('get-service-providers-by-role-id')
        ->middleware(['auth:api']);
});
