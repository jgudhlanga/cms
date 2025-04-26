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
        foreach (DepartmentEnum::cases() as $row) {
            Department::create(['name' => $row->value]);
        }
    }
}
