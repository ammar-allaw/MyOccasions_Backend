<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles_en = ['owner', 'client','halls', 'clothing stores ','banquet coordinators','aradas','photographers','restaurants','chocolate stores','singers','makeup saloons','flower stores'];
        $roles_ar = ['مالك', 'زبون','صالات', 'محلات الملابس ','منسقي الحفلات','عراضات','مصورين','مطاعم','محلات الشوكولا','منشدون','صالونات المكياج','محلات الزهور'];

        $permissions = [
        'assign_permission_for_role','add_permission','add_service_provider',
        'update_service_provider','soft_service_provider','get_soft_delete_service_provider',
        'delete_service_provider','restore_service_provider','get_service_provider',
        //,'accept_image',get_image_not_allow
        //     ,'get_statusable_by_status_id','accept_statusAble_id',
        //     'reject_statusAble_id','get-users-with-trashed-by-id-role',

        //     'add_room','add_service','update_service','add_image_room','add_image_service',


        //     // 'add_hall', 'delete_hall', 'update_hall', 'accept_photo','show_hall','add_room','delete_room','update_room','show_room',
        //     // 'add_clothing_store','delete_clothing_store','update_clothing_store','show_clothing_store','add_product','update_product','delete_product','show_product',
        //     // 'add_banquet_coordinator','delete_banquet_coordinator','update_banquet_coordinator','show_banquet_coordinator',
        //     // 'add_arada','delete_arada','update_arada','show_arada',
        //     // 'add_photographer','delete_photographer','update_photographer','show_photographer',
        ];

        foreach ($roles_ar as $index => $role_ar) {
            Role::create([
                'name' => $role_ar,  // English name
                'name_en' => $roles_en[$index], // Arabic name (matching index)
            ]);
        }

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $ownerRole = Role::where('name_en','owner')->first();
        $allPermissions = Permission::all();

        foreach ($allPermissions as $permission) {
            $ownerRole->permissions()->attach($permission->id, ['allowed' => true]);
        }
    }
}
