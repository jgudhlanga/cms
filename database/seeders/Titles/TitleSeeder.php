<?php

namespace Database\Seeders\Titles;

use App\Enums\TitleEnum;
use App\Models\Titles\Title;
use Illuminate\Database\Seeder;

class TitleSeeder extends Seeder
{
    public function run(): void
    {

        foreach (TitleEnum::cases() as $row) {
            Title::create(['name' => $row->value]);
        }
    }
}
