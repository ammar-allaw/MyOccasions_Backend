<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::controller(OwnerController::class)->prefix('owner')
->group(function(){
        Route::post('/add-service-provider','addServiceProvider')->name('add-service-provider')
        ->middleware(['auth:owner']);

        Route::get('/get-roles-for-owner','getRolesForOwner')->name('get-roles-for-owner')
        ->middleware(['auth:owner']);

        Route::get('/get-service-providers/{roleId?}','getServiceProvidersByRoleIdForOwner')->name('get-service-providers')
        ->middleware(['auth:owner']);

        
        
        //not used now by ammar
        Route::get('/get-all-rooms-with-status','getAllRoomsWithStatus')->name('get-all-rooms-with-status')
        ->middleware(['auth:owner']);

        Route::delete('/force-delete-service-provider/{serviceProviderId}','forceDeleteServiceProvider')->name('force-delete-service-provider')
        ->middleware(['auth:owner']);

        Route::get('/get-service-providers-with-trashed','getServiceProvidersWithTrashed')->name('get-service-providers-with-trashed')
        ->middleware(['auth:owner']);

        Route::delete('/soft-delete-service-provider/{serviceProviderId}','softDeleteServiceProvider')->name('soft-delete-service-provider')
        ->middleware(['auth:owner']);
});

// ─── Roles (owner only) ───────────────────────────────────────────────────────
Route::prefix('owner')->middleware('auth:owner')->group(function () {

    // CRUD for roles
    Route::controller(RoleController::class)->prefix('roles')->group(function () {
        Route::get('/',            'index');          // GET    /owner/roles
        Route::post('/',           'store');          // POST   /owner/roles
        Route::get('/{roleId}',    'show');           // GET    /owner/roles/{roleId}
        Route::put('/{roleId}',    'update');         // PUT    /owner/roles/{roleId}
        Route::delete('/{roleId}', 'destroy');        // DELETE /owner/roles/{roleId}

        // Assign / revoke / sync permissions on a role
        Route::post('/{roleId}/permissions',                     'assignPermission');  // POST   /owner/roles/{roleId}/permissions
        Route::delete('/{roleId}/permissions/{permissionId}',    'revokePermission'); // DELETE /owner/roles/{roleId}/permissions/{permissionId}
        Route::put('/{roleId}/permissions/sync',                 'syncPermissions');  // PUT    /owner/roles/{roleId}/permissions/sync
    });

    // Assign / revoke roles on a specific user
    Route::controller(RoleController::class)->prefix('users/{userId}/roles')->group(function () {
        Route::post('/',           'assignRoleToUser');           // POST   /owner/users/{userId}/roles
        Route::delete('/{roleId}', 'revokeRoleFromUser');        // DELETE /owner/users/{userId}/roles/{roleId}
    });

    // ─── Permissions (owner only) ─────────────────────────────────────────────
    Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
        Route::get('/',                 'index');     // GET    /owner/permissions
        Route::post('/',                'store');     // POST   /owner/permissions
        Route::get('/{permissionId}',   'show');      // GET    /owner/permissions/{permissionId}
        Route::delete('/{permissionId}','destroy');   // DELETE /owner/permissions/{permissionId}
    });

    // Assign / revoke permissions on a specific user
    Route::controller(PermissionController::class)->prefix('users/{userId}/permissions')->group(function () {
        Route::post('/',                  'assignToUser');        // POST   /owner/users/{userId}/permissions
        Route::delete('/{permissionId}',  'revokeFromUser');     // DELETE /owner/users/{userId}/permissions/{permissionId}
    });

    // Owner: get any user's role & user permissions
    
});
Route::get('/users/get-permissions/{userId?}', [OwnerController::class, 'getUserPermissions'])
        ->name('owner-get-user-permissions')
        ->middleware(['auth.provider.or.owner']);
