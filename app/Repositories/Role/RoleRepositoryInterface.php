<?php
//new added
namespace App\Repositories\Role;

interface RoleRepositoryInterface
{
    public function all();
    public function findById(int $id);
    public function create(array $data);
    public function update($role, array $data);
    public function delete($role): bool;
    public function assignPermission($role, int $permissionId): void;
    public function revokePermission($role, int $permissionId): void;
    public function syncPermissions($role, array $permissionIds): void;
}
