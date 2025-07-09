<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Shared\WorkflowStep;
use Illuminate\Database\Seeder;

class WorkflowStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (WorkflowStepEnum::cases() as $row) {
            WorkflowStep::create([
                'name' => $row->name(),
                'description' => $row->description(),
                'position' => $row->position(),
            ]);
        }
    }
}
