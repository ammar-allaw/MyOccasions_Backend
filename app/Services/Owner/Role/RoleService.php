<?php
//new added
namespace App\Services\Owner\Role;

use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\User\UserServiceInterface;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private UserServiceInterface $userService
    ) {}

    public function listRoles()
    {
        return $this->roleRepository->all();
    }

    public function findRole(int $id)
    {
        return $this->roleRepository->findById($id);
    }

    public function createRole(array $data)
    {
        return $this->roleRepository->create($data);
    }

    public function updateRole(int $id, array $data)
    {
        $role = $this->roleRepository->findById($id);
        return $this->roleRepository->update($role, $data);
    }

    public function deleteRole(int $id): bool
    {
        $role = $this->roleRepository->findById($id);
        return $this->roleRepository->delete($role);
    }

    public function assignPermissionToRole(int $roleId, int $permissionId): void
    {
        $role = $this->roleRepository->findById($roleId);
        $this->roleRepository->assignPermission($role, $permissionId);
    }

    public function revokePermissionFromRole(int $roleId, int $permissionId): void
    {
        $role = $this->roleRepository->findById($roleId);
        $this->roleRepository->revokePermission($role, $permissionId);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $role = $this->roleRepository->findById($roleId);
        $this->roleRepository->syncPermissions($role, $permissionIds);
    }

    public function assignRoleToUser(int $userId, int $roleId, bool $allowed = true): void
    {
        $user = $this->userService->findUserById($userId);
        $user->userRoles()->syncWithoutDetaching([
            $roleId => ['allowed' => $allowed],
        ]);
    }

    public function revokeRoleFromUser(int $userId, int $roleId): void
    {
        $user = $this->userService->findUserById($userId);
        $user->userRoles()->detach($roleId);
    }
}
