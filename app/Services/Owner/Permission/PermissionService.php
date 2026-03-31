<?php
//new added
namespace App\Services\Owner\Permission;

use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Services\User\UserServiceInterface;

class PermissionService implements PermissionServiceInterface
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
        private UserServiceInterface $userService
    ) {}

    public function listPermissions()
    {
        return $this->permissionRepository->all();
    }

    public function findPermission(int $id)
    {
        return $this->permissionRepository->findById($id);
    }

    public function createPermission(array $data)
    {
        return $this->permissionRepository->create($data);
    }

    public function updatePermission(int $id, array $data)
    {
        $permission = $this->permissionRepository->findById($id);
        return $this->permissionRepository->update($permission, $data);
    }

    public function deletePermission(int $id): bool
    {
        $permission = $this->permissionRepository->findById($id);
        return $this->permissionRepository->delete($permission);
    }

    public function assignPermissionToUser(int $userId, int $permissionId, bool $allowed = true): void
    {
        $user = $this->userService->findUserById($userId);
        $this->permissionRepository->assignToUser($user, $permissionId, $allowed);
    }

    public function revokePermissionFromUser(int $userId, int $permissionId): void
    {
        $user = $this->userService->findUserById($userId);
        $this->permissionRepository->revokeFromUser($user, $permissionId);
    }
}
