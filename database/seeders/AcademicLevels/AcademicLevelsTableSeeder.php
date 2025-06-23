<?php

namespace Database\Seeders\AcademicLevels;

use App\Enums\AcademicLevelEnum;
use App\Models\AcademicLevels\AcademicLevel;
use Illuminate\Database\Seeder;

class AcademicLevelsTableSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => AcademicLevelEnum::PRIMARY_SCHOOL->value, 'position' => 1],
            ['name' => AcademicLevelEnum::SECONDARY_SCHOOL->value, 'position' => 2],
            ['name' => AcademicLevelEnum::ADVANCED_LEVEL->value, 'position' => 3],
        ];
        foreach ($data as $row) {
            AcademicLevel::create(['name' => $row['name'], 'position' => $row['position']]);;
        }
    }
}
