<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/app/get-roles', [RoleController::class, 'getRoles'])
    ->name('get-roles')
    ->middleware(['auth:api']);
