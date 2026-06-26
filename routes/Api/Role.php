<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::controller(RoleController::class)->prefix('owner')->middleware('auth:owner')->group(function () {
    Route::get('/get-roles-for-owner', 'getRolesForOwner')->name('get-roles-for-owner');
});

Route::get('/app/get-roles', [RoleController::class, 'getRoles'])
    ->name('get-roles')
    ->middleware(['auth:api']);
