<?php
//new added
namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AssignPermissionToRoleRequest;
use App\Http\Requests\Owner\AssignRoleToUserRequest;
use App\Http\Requests\Owner\StoreRoleRequest;
use App\Http\Requests\Owner\SyncRolePermissionsRequest;
use App\Http\Requests\Owner\UpdateRoleRequest;
use App\Services\Owner\Role\RoleServiceInterface;
use Exception;

class RoleController extends Controller
{
    public function __construct(
        private Handler $handler,
        private RoleServiceInterface $roleService
    ) {}

    public function index()
    {
        try {
            $roles = $this->roleService->listRoles();
            return $this->handler->successResponse(
                ['roles' => $roles],
                true,
                'Roles retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $role = $this->roleService->createRole($request->validated());
            return $this->handler->successResponse(
                ['role' => $role],
                true,
                'Role created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function show(int $roleId)
    {
        try {
            $role = $this->roleService->findRole($roleId);
            return $this->handler->successResponse(
                ['role' => $role],
                true,
                'Role retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 404);
        }
    }

    public function update(UpdateRoleRequest $request, int $roleId)
    {
        try {
            $role = $this->roleService->updateRole($roleId, $request->validated());
            return $this->handler->successResponse(
                ['role' => $role],
                true,
                'Role updated successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function destroy(int $roleId)
    {
        try {
            $this->roleService->deleteRole($roleId);
            return $this->handler->successResponse(null, true, 'Role deleted successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // POST /owner/roles/{roleId}/permissions — assign a single permission to a role
    public function assignPermission(AssignPermissionToRoleRequest $request, int $roleId)
    {
        try {
            $this->roleService->assignPermissionToRole($roleId, $request->validated()['permission_id']);
            $role = $this->roleService->findRole($roleId);
            return $this->handler->successResponse(
                ['role' => $role],
                true,
                'Permission assigned to role successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // DELETE /owner/roles/{roleId}/permissions/{permissionId} — revoke a permission from a role
    public function revokePermission(int $roleId, int $permissionId)
    {
        try {
            $this->roleService->revokePermissionFromRole($roleId, $permissionId);
            return $this->handler->successResponse(null, true, 'Permission revoked from role successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // PUT /owner/roles/{roleId}/permissions/sync — replace all permissions for a role
    public function syncPermissions(SyncRolePermissionsRequest $request, int $roleId)
    {
        try {
            $this->roleService->syncRolePermissions($roleId, $request->validated()['permission_ids']);
            $role = $this->roleService->findRole($roleId);
            return $this->handler->successResponse(
                ['role' => $role],
                true,
                'Role permissions synced successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // POST /owner/users/{userId}/roles — assign a role to a user
    public function assignRoleToUser(AssignRoleToUserRequest $request, int $userId)
    {
        try {
            $data    = $request->validated();
            $allowed = $data['allowed'] ?? true;
            $this->roleService->assignRoleToUser($userId, $data['role_id'], $allowed);
            return $this->handler->successResponse(null, true, 'Role assigned to user successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // DELETE /owner/users/{userId}/roles/{roleId} — revoke a role from a user
    public function revokeRoleFromUser(int $userId, int $roleId)
    {
        try {
            $this->roleService->revokeRoleFromUser($userId, $roleId);
            return $this->handler->successResponse(null, true, 'Role revoked from user successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
