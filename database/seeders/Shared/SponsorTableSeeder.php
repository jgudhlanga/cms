<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\SponsorTypeEnum;
use App\Models\Shared\SponsorType;
use Illuminate\Database\Seeder;

class SponsorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SponsorTypeEnum::cases() as $row) {
            SponsorType::create(['name' => $row->value]);
        }
    }
}
