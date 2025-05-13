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
        $data = [
            ['name' => LevelEnum::ABMA_LEVEL_3->value, 'position' => 1],
            ['name' => LevelEnum::ABMA_LEVEL_4->value, 'position' => 2],
            ['name' => LevelEnum::ABMA_LEVEL_5->value, 'position' => 3],
            ['name' => LevelEnum::ABMA_LEVEL_6->value, 'position' => 4],
            ['name' => LevelEnum::NC->value, 'position' => 5],
            ['name' => LevelEnum::ND->value, 'position' => 6],
            ['name' => LevelEnum::HND->value, 'position' => 7],
            ['name' => LevelEnum::BTECH->value, 'position' => 8],
            ['name' => LevelEnum::SDP->value, 'position' => 9],
        ];
        foreach ($data as $row) {
            Level::create(['name' => $row['name'], 'position' => $row['position']]);;
        }
    }
}
