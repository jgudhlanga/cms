<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\ApplicationStepEnum;
use App\Models\Shared\ApplicationStep;
use Illuminate\Database\Seeder;

class ApplicationStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name', '', 'description', '']
        ];
        foreach (ApplicationStepEnum::cases() as $row) {
            ApplicationStep::create(['name' => $row->value]);
        }
    }
}
