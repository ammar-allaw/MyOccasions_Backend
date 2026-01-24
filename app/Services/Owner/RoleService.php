<?php

namespace App\Services\Owner;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleService
{

    public function find_role_by_id($role_id){
            $role=Role::find($role_id);
            if($role){
                return $role;
            }
            throw new NotFoundHttpException('role not found');
    }

    public function getRolesExceptOwner(){
        $roles=Role::where('name','!=','owner')->get();
        return $roles;
    }

    public function getUsersByIdOfRole(Role $role){
        $users=User::where('role_id',$role->id)->get();
        return $users;
    }

    public function getUsersWithTrashedByIdOfRole(Role $role){
        $users=User::withTrashed()->where('role_id',$role->id)->get();
        return $users;
    }

  
}
