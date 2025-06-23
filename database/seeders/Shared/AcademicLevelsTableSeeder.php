<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\AcademicLevelEnum;
use App\Models\Shared\AcademicLevel;
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
