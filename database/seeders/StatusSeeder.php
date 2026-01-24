<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status1=Status::create([
            'name'=>'مقبول',
            'name_en'=>"accepted",
        ]);
        $status2=Status::create([
            'name'=>'مرفوض',
            'name_en'=>"rejected",

        ]);
        $status3=Status::create([
            'name'=>'قيد المراجعة',
            'name_en'=>"under_review",
        ]);
    }
}
