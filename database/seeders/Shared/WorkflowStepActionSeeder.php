<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\WorkflowStepActionEnum;
use App\Models\Shared\WorkflowStepAction;
use Illuminate\Database\Seeder;

class WorkflowStepActionSeeder extends Seeder
{

    public function run(): void
    {
        foreach (WorkflowStepActionEnum::cases() as $row) {
            WorkflowStepAction::create(['title' => $row->title()]);
        }
    }
}
