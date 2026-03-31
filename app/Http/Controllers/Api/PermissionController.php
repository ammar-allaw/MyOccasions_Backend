<?php
//new added
namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AssignPermissionToUserRequest;
use App\Http\Requests\Owner\StorePermissionRequest;
use App\Services\Owner\Permission\PermissionServiceInterface;
use Exception;

class PermissionController extends Controller
{
    public function __construct(
        private Handler $handler,
        private PermissionServiceInterface $permissionService
    ) {}

    public function index()
    {
        try {
            $permissions = $this->permissionService->listPermissions();
            return $this->handler->successResponse(
                ['permissions' => $permissions],
                true,
                'Permissions retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function store(StorePermissionRequest $request)
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());
            return $this->handler->successResponse(
                ['permission' => $permission],
                true,
                'Permission created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function show(int $permissionId)
    {
        try {
            $permission = $this->permissionService->findPermission($permissionId);
            return $this->handler->successResponse(
                ['permission' => $permission],
                true,
                'Permission retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 404);
        }
    }

    public function destroy(int $permissionId)
    {
        try {
            $this->permissionService->deletePermission($permissionId);
            return $this->handler->successResponse(null, true, 'Permission deleted successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // POST /owner/users/{userId}/permissions — assign a permission to a user
    public function assignToUser(AssignPermissionToUserRequest $request, int $userId)
    {
        try {
            $data    = $request->validated();
            $allowed = $data['allowed'] ?? true;
            $this->permissionService->assignPermissionToUser($userId, $data['permission_id'], $allowed);
            return $this->handler->successResponse(null, true, 'Permission assigned to user successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    // DELETE /owner/users/{userId}/permissions/{permissionId} — revoke a permission from a user
    public function revokeFromUser(int $userId, int $permissionId)
    {
        try {
            $this->permissionService->revokePermissionFromUser($userId, $permissionId);
            return $this->handler->successResponse(null, true, 'Permission revoked from user successfully', 200);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
