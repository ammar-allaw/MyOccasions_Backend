<?php
//new added
namespace App\Services\Owner\Role;

interface RoleServiceInterface
{
    public function listRoles();
    public function findRole(int $id);
    public function createRole(array $data);
    public function updateRole(int $id, array $data);
    public function deleteRole(int $id): bool;
    public function assignPermissionToRole(int $roleId, int $permissionId): void;
    public function revokePermissionFromRole(int $roleId, int $permissionId): void;
    public function syncRolePermissions(int $roleId, array $permissionIds): void;
    public function assignRoleToUser(int $userId, int $roleId, bool $allowed = true): void;
    public function revokeRoleFromUser(int $userId, int $roleId): void;
}
