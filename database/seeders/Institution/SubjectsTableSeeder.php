<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\SubjectEnum;
use App\Models\Institution\Subject;
use Illuminate\Database\Seeder;

class SubjectsTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SubjectEnum::cases() as $row) {
            Subject::create(['name' => $row->value, 'position' => $row->id()]);
        }
    }
}
