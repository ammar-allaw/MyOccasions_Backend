<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public function users(){
        return $this->hasMany(User::class);
    }

    public function owners(){
        return $this->hasMany(Owner::class,'owner_id');
    }

    public function permissions(){
        return $this->belongsToMany(Permission::class,'role_permissions')->withPivot('allowed');
    }

    public function checkRoleIfHasPermission($role,$permission_id){
        $role_has_permission=$role->permissions()->where('permission_id', $permission_id)->exists();
        if($role_has_permission){
            return true;
        }
        return false;
    }
}
