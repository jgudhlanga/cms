<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\ApplicationStepEnum;
use App\Models\Institution\ApplicationStep;
use Illuminate\Database\Seeder;

class ApplicationStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ApplicationStepEnum::cases() as $row) {
            ApplicationStep::create(['name' => $row->value]);
        }
    }
}
