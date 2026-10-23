<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Owner\PermissionController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
->group(function(){
        Route::post('login','login')->name('login');
        Route::post('login-service-provider','loginServiceProvider')->name('login-service-provider');
        Route::get('profile','profile')->name('profile')->middleware('auth:api');
        Route::post('logout','logout')->name('logout')->middleware('auth:api');
        Route::post('register','register')->name('register')->middleware('throttle:register-otp');
        Route::post('register/verify-otp','verifyRegistrationOtp')->name('register.verify-otp')->middleware('throttle:verify-register-otp');
        Route::post('register/resend-otp','resendRegistrationOtp')->name('register.resend-otp')->middleware('throttle:resend-register-otp');
        Route::post('/login-owner','loginOwner')->name('/login-owner');
        Route::post('/reset-password','resetPassword')->name('/reset-password')
        ->middleware('auth:owner');
        Route::post('/change-password','changePassword')->name('/change-password')
        ->middleware('auth:api');

        
});
