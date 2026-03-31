<?php

namespace App\Repositories\Permission;

use App\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(private Permission $model) {}

    public function all()
    {
        return $this->model->with('roles')->get();
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($permission, array $data)
    {
        $permission->update($data);
        return $permission->refresh();
    }

    public function delete($permission): bool
    {
        return $permission->delete();
    }

    public function assignToUser($user, int $permissionId, bool $allowed = true): void
    {
        $user->userPermissions()->syncWithoutDetaching([
            $permissionId => ['allowed' => $allowed],
        ]);
    }

    public function revokeFromUser($user, int $permissionId): void
    {
        $user->userPermissions()->detach($permissionId);
    }
}
