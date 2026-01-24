<?php

namespace App\Services\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthService
{

    public function authUser()
    {
        // Try api guard first (service providers/halls)
        if (Auth::guard('api')->check()) {
            return Auth::guard('api')->user();
        }

        // Then try owner guard
        // if (Auth::guard('owner')->check()) {
        //     return Auth::guard('owner')->user();
        // }

        // throw new Exception('auth user not found');
    }

    public function userable($user)
    {
        $userable=$user->userable;
        return $userable;
    }

    public function findRoleById($roleId)
    {
        $role=Role::find($roleId);
        if(!$role){
            throw new NotFoundHttpException('Role not found');
        }
        return $role;
    }

    // public function create_permission(array $data)
    // {
    //         $permission_create=Permission::create($data);
    //         return $permission_create;
    // }

    // // public function getAllAdmin(){
    // //     $admins=User::where('admin','=',1)->get();
    // //     return $admins;
    // // }

    public function getRole()
    {
        $roles = Role::where('name_en', '!=', 'owner')->where('name_en','!=','client')->get();
        return $roles;
    }
}
