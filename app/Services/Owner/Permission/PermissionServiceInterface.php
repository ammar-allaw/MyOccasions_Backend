<?php
//new added
namespace App\Services\Owner\Permission;

interface PermissionServiceInterface
{
    public function listPermissions();
    public function findPermission(int $id);
    public function createPermission(array $data);
    public function updatePermission(int $id, array $data);
    public function deletePermission(int $id): bool;
    public function assignPermissionToUser(int $userId, int $permissionId, bool $allowed = true): void;
    public function revokePermissionFromUser(int $userId, int $permissionId): void;
}
