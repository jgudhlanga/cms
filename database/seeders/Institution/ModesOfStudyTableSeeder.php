<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Database\Seeder;

class ModesOfStudyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ModeOfStudyEnum::cases() as $row) {
            ModeOfStudy::create(['name' => $row->value]);
        }
    }
}
