<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Owner::factory()->create([
            'name'=>'Ammar Allaw',
            'email'=>'ammarallaw201@gmail.com',
            'role_id'=>1,
            'password'=>Hash::make(123456789),
        ])->count();
    }
}
