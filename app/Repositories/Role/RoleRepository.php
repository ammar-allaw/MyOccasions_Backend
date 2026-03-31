<?php
//new added
namespace App\Repositories\Role;

use App\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(private Role $model) {}

    public function all()
    {
        return $this->model->with('permissions')->get();
    }

    public function findById(int $id)
    {
        return $this->model->with('permissions')->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($role, array $data)
    {
        $role->update($data);
        return $role->refresh();
    }

    public function delete($role): bool
    {
        return $role->delete();
    }

    public function assignPermission($role, int $permissionId): void
    {
        $role->permissions()->syncWithoutDetaching([$permissionId]);
    }

    public function revokePermission($role, int $permissionId): void
    {
        $role->permissions()->detach($permissionId);
    }

    public function syncPermissions($role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }
}
