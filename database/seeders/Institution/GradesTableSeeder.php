<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\GradeEnum;
use App\Models\Institution\Grade;
use Illuminate\Database\Seeder;

class GradesTableSeeder extends Seeder
{

    public function run(): void
    {

        $data = [
            ['name' => GradeEnum::A->value, 'position' => 1],
            ['name' => GradeEnum::B->value, 'position' => 2],
            ['name' => GradeEnum::C->value, 'position' => 3],
            ['name' => GradeEnum::D->value, 'position' => 4],
            ['name' => GradeEnum::E->value, 'position' => 5],
            ['name' => GradeEnum::U->value, 'position' => 6],
        ];
        foreach ($data as $row) {
            Grade::create(['name' => $row['name'], 'position' => $row['position']]);
        }
    }
}
