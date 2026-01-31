<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
->group(function(){
        Route::post('login','login')->name('login');
        Route::post('logout','logout')->name('logout')->middleware('auth:api');
        Route::post('register','register')->name('register');
        Route::post('/login-owner','loginOwner')->name('/login-owner');
        Route::post('/reset-password','resetPassword')->name('/reset-password')
        ->middleware('auth:owner');
        Route::post('/change-password','changePassword')->name('/change-password')
        ->middleware('auth:api');
});