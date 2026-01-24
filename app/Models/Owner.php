<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Owner extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;


    protected $hidden = [
        'password',
    ];

    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }

    public function hasPermission($permissionName)
    {
        if ($this->role->permissions()->where('name', $permissionName)->where('allowed', true)->exists()) {
            return true;
        }
        return false;
    }


}
