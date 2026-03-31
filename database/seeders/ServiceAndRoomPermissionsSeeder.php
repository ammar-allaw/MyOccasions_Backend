<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ServiceAndRoomPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $servicePermissions = ['add_service', 'update_service', 'get_services', 'delete_service'];
        $roomPermissions    = ['add_room', 'update_room', 'get_rooms', 'delete_room'];

        // Create permissions if they don't exist
        foreach (array_merge($servicePermissions, $roomPermissions) as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Role ID 3 → service + room permissions
        $role3 = Role::find(3);
        if ($role3) {
            foreach (array_merge($servicePermissions, $roomPermissions) as $name) {
                $permission = Permission::where('name', $name)->first();
                if ($permission && !$role3->permissions()->where('permission_id', $permission->id)->exists()) {
                    $role3->permissions()->attach($permission->id, ['allowed' => true]);
                }
            }
        }

        // Roles 5, 6, 7, 10, 11 → service permissions only
        $otherRoleIds = [5, 6, 7, 10, 11];
        foreach ($otherRoleIds as $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                foreach ($servicePermissions as $name) {
                    $permission = Permission::where('name', $name)->first();
                    if ($permission && !$role->permissions()->where('permission_id', $permission->id)->exists()) {
                        $role->permissions()->attach($permission->id, ['allowed' => true]);
                    }
                }
            }
        }
    }
}
