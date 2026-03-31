<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
    //new added
    // Permission <-> Role (role_permissions pivot — no allowed column)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // Users that have this permission assigned directly via user_permissions pivot
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withPivot('allowed')
                    ->withTimestamps();
    }
}
