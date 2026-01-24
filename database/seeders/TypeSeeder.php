<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status1=Type::create([
            'name'=>'افراح',
            'name_en'=>"weddings"
        ]);
        $status2=Type::create([
            'name'=>'تماسي',
            'name_en'=>"condolences",
        ]);
        
        // $status3=Type::create([
        //     'name'=>'weddings review'
        // ]);
        // $status3->name_ar="قيد المراجعة";
        // $status3->save();
    }
}
