<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\CourseEnum;
use App\Models\Institution\Course;
use Illuminate\Database\Seeder;

class CoursesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CourseEnum::cases() as $row) {
            $exist = Course::where('name', $row->value)->first();
            if (!$exist instanceof Course) {
                Course::create([
                    'name' => $row->value,
                    'position' => $row->position(),
                    'description' => $row->description(),
                ]);
            }

        }
    }
}
