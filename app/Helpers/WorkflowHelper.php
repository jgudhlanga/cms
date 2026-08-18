<?php

namespace App\Helpers;

use App\Models\Shared\WorkflowStep;
use Illuminate\Database\Eloquent\Collection;

class WorkflowHelper
{
    public static function getStepByPosition(int $position): ?WorkflowStep
    {
        return WorkflowStep::query()->where('position', $position)->first();
    }

    public static function getAllPendingSteps(int $currentPosition): Collection
    {
        return WorkflowStep::query()->where('position', '>', $currentPosition)->orderBy('position')->get();
    }

    public static function getAllSteps(): Collection
    {
        return WorkflowStep::query()->orderBy('position')->get();
    }

    public static function getMaxStep(): ?WorkflowStep
    {
        return WorkflowStep::query()->orderByDesc('position')->first();
    }
}
