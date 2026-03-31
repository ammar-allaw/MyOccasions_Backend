<?php
//new added
namespace App\Repositories\Permission;

interface PermissionRepositoryInterface
{
    public function all();
    public function findById(int $id);
    public function create(array $data);
    public function update($permission, array $data);
    public function delete($permission): bool;
    public function assignToUser($user, int $permissionId, bool $allowed = true): void;
    public function revokeFromUser($user, int $permissionId): void;
}
