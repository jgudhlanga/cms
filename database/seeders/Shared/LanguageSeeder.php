<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\LanguageEnum;
use App\Models\Shared\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (LanguageEnum::cases() as $row) {
            $exist = Language::where('title', $row->value)->first();
            if (! $exist instanceof Language) {
                Language::create(['title' => $row->value]);
            }
        }
    }
}
