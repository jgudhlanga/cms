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
        foreach (SubjectEnum::cases() as $row) {
            Subject::create(['name' => $row->value]);
        }
    }
}
