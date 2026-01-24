<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GovernmentController;
use App\Http\Controllers\Owner\PermissionController;
use App\Models\Government;
use Illuminate\Support\Facades\Route;

Route::controller(GovernmentController::class)
->prefix('government')
->group(function(){
        Route::get('get-governments','getGovernments')->name('get-governments');
});


Route::controller(GovernmentController::class)
->prefix('region')
->group(function(){
        Route::post('add-region','addRegion')->name('add-region')->middleware('auth:owner');
        Route::get('get-regions-by-government/{government_id}','getRegionsByGovernment')->name('get-regions-by-government');
});



