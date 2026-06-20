<?php

namespace App\Repositories\Role\Implementation;

use App\Models\Role;
use App\Repositories\Role\Interface\RoleRepositoryInterface;

class RoleRepository implements RoleRepositoryInterface
{
    private const DEFAULT_WITH = ['permissions'];

    private const BROWSABLE_WITH = ['mainKeys'];

    public function __construct(private Role $model) {}

    public function all()
    {
        return $this->model->with(self::DEFAULT_WITH)->get();
    }

    public function findById(int $id)
    {
        return $this->model->with(self::DEFAULT_WITH)->findOrFail($id);
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

    public function getBrowsableForClient()
    {
        return $this->model->with(self::BROWSABLE_WITH)
            ->where('name_en', '!=', 'owner')
            ->where('name_en', '!=', 'client')
            ->get();
    }
}
