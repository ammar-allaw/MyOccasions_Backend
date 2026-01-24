<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $governments = [
            ['name' => 'دمشق', 'name_en' => 'Damascus'],
            ['name' => 'ريف دمشق', 'name_en' => 'Rif Dimashq'],
            ['name' => 'حلب', 'name_en' => 'Aleppo'],
            ['name' => 'حمص', 'name_en' => 'Homs'],
            ['name' => 'اللاذقية', 'name_en' => 'Latakia'],
            ['name' => 'حماة', 'name_en' => 'Hama'],
            ['name' => 'طرطوس', 'name_en' => 'Tartus'],
            ['name' => 'ادلب', 'name_en' => 'Idlib'],
            ['name' => 'الحسكة', 'name_en' => 'Al-Hasakah'],
            ['name' => 'دير الزور', 'name_en' => 'Deir-ez-Zor'],
            ['name' => 'الرقة', 'name_en' => 'Raqqa'],
            ['name' => 'السويداء', 'name_en' => 'As-Suwayda'],
            ['name' => 'درعا', 'name_en' => 'Daraa'],
            ['name' => 'القنيطرة', 'name_en' => 'Quneitra'],
        ];

        DB::table('governments')->insert($governments);
    }
}
