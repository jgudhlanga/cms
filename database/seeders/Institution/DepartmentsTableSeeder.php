<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\DepartmentEnum;
use App\Models\Institution\Department;
use Illuminate\Database\Seeder;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => DepartmentEnum::APPLIED_ARTS->value, 'position' => 1 ],
            ['name' => DepartmentEnum::AUTOMOTIVE_ENGINEERING->value, 'position' => 2 ],
            ['name' => DepartmentEnum::BUSINESS_AND_MANAGEMENT_STUDIES->value, 'position' => 3 ],
            ['name' => DepartmentEnum::CIVIL_ENGINEERING->value, 'position' => 4 ],
            ['name' => DepartmentEnum::CONSTRUCTION_ENGINEERING->value, 'position' => 5 ],
            ['name' => DepartmentEnum::ELECTRICAL_ENGINEERING->value, 'position' => 6 ],
            ['name' => DepartmentEnum::INFORMATION_COMMUNICATION_TECHNOLOGY->value, 'position' => 7 ],
            ['name' => DepartmentEnum::LIBRARY_AND_INFORMATION_SCIENCES->value, 'position' => 8 ],
            ['name' => DepartmentEnum::MASS_COMMUNICATION->value, 'position' => 9 ],
            ['name' => DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->value, 'position' => 10 ],
            ['name' => DepartmentEnum::OFFICE_MANAGEMENT->value, 'position' => 11 ],
            ['name' => DepartmentEnum::PRINTING_AND_GRAPHIC_ARTS->value, 'position' => 12 ],
            ['name' => DepartmentEnum::SCIENCE_TECHNOLOGY->value, 'position' => 13 ],
            ['name' => DepartmentEnum::TOURISM_AND_HOSPITALITY->value, 'position' => 14 ],
        ];
        foreach ($data as $row) {
            Department::create(['name' => $row['name'], 'position' => $row['position']]);
        }
    }
}
