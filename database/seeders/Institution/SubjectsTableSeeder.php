<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\SubjectEnum;
use App\Models\Institution\Subject;
use Illuminate\Database\Seeder;

class SubjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data = [
            ['name' => SubjectEnum::ACCOUNTING->value, 'position' => 1],
            ['name' => SubjectEnum::AGRICULTURE->value, 'position' => 2],
            ['name' => SubjectEnum::ART->value, 'position' => 3],
            ['name' => SubjectEnum::BIBLE_KNOWLEDGE->value, 'position' => 4],
            ['name' => SubjectEnum::BUILDING_STUDIES->value, 'position' => 5],
            ['name' => SubjectEnum::BUSINESS_AND_ENTERPRISE_SKILLS->value, 'position' => 6],
            ['name' => SubjectEnum::BUSINESS_STUDIES->value, 'position' => 7],
            ['name' => SubjectEnum::CHINESE->value, 'position' => 8],
            ['name' => SubjectEnum::COMMERCE->value, 'position' => 9],
            ['name' => SubjectEnum::COMPUTER_SCIENCE->value, 'position' => 10],
            ['name' => SubjectEnum::DESIGN_AND_TECHNOLOGY->value, 'position' => 11],
            ['name' => SubjectEnum::ECONOMICS->value, 'position' => 12],
            ['name' => SubjectEnum::ENGLISH->value, 'position' => 13],
            ['name' => SubjectEnum::FASHION_AND_FABRICS->value, 'position' => 14],
            ['name' => SubjectEnum::FOOD_AND_NUTRITION->value, 'position' => 15],
            ['name' => SubjectEnum::FRENCH->value, 'position' => 16],
            ['name' => SubjectEnum::GEOGRAPHY->value, 'position' => 17],
            ['name' => SubjectEnum::GERMAN->value, 'position' => 18],
            ['name' => SubjectEnum::HISTORY->value, 'position' => 19],
            ['name' => SubjectEnum::INTEGRATED_SCIENCE->value, 'position' => 20],
            ['name' => SubjectEnum::LITERATURE_IN_ENGLISH->value, 'position' => 21],
            ['name' => SubjectEnum::MATHEMATICS->value, 'position' => 22],
            ['name' => SubjectEnum::METAL_TECHNOLOGY_AND_DESIGN->value, 'position' => 23],
            ['name' => SubjectEnum::MUSIC->value, 'position' => 24],
            ['name' => SubjectEnum::NDEBELE->value, 'position' => 25],
            ['name' => SubjectEnum::PHYSICAL_EDUCATION->value, 'position' => 26],
            ['name' => SubjectEnum::RELIGIOUS_STUDIES->value, 'position' => 27],
            ['name' => SubjectEnum::SHONA->value, 'position' => 28],
            ['name' => SubjectEnum::SPANISH->value, 'position' => 29],
            ['name' => SubjectEnum::TECHNICAL_GRAPHICS->value, 'position' => 30],
            ['name' => SubjectEnum::WOOD_TECHNOLOGY_AND_DESIGN->value, 'position' => 31],
        ];
        foreach ($data as $row) {
            Subject::create(['name' => $row['name'], 'position' => $row['position']]);
        }
    }
}
