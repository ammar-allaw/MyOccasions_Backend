<?php

namespace App\Services\Owner;

use App\Models\Permission;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermissionService
{

    public function find_permission_by_id($permission_id){
            $permission=Permission::find($permission_id);
            if($permission){
                return $permission;
            }
            throw new NotFoundHttpException('permission not found');
    }

    public function create_permission(array $data)
    {
            $permission_create=Permission::create($data);
            return ['permission'=>$permission_create];
    }

    public function add_permission_to_role($role,$data){
        $role->permissions()->attach($data['permission_id'] , ['allowed' => $data['allowed']]);
    }

}
