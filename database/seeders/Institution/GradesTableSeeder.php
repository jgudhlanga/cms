<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\GradeEnum;
use App\Models\Institution\Grade;
use Illuminate\Database\Seeder;

class GradesTableSeeder extends Seeder
{

    public function run(): void
    {
        foreach (GradeEnum::cases() as $row) {
            Grade::create(['name' => $row->value, 'position' => $row->position()]);
        }
    }
}
