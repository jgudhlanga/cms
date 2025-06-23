<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\TitleEnum;
use App\Models\Shared\Title;
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
