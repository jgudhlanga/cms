<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\GenderEnum;
use App\Models\Shared\Gender;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (GenderEnum::cases() as $row) {
            $exist = Gender::where('title', $row->value)->first();
            if (!$exist instanceof Gender) {
                Gender::create(['title' => $row->value]);
            }
        }
    }
}
