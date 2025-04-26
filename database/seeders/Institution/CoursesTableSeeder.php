<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\CourseEnum;
use App\Models\Institution\Course;
use Illuminate\Database\Seeder;

class CoursesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (CourseEnum::cases() as $row) {
            Course::create(['name' => $row->value]);
        }
    }
}
