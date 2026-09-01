<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\LevelEnum;
use App\Models\Institution\Level;
use Illuminate\Database\Seeder;

class LevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (LevelEnum::cases() as $row) {
            Level::updateOrCreate(
                ['name' => $row->name()],
                [
                    'description' => $row->description(),
                    'position' => $row->position(),
                    'calendar_type' => $row->calendarType()->value,
                ],
            );
        }
    }
}
