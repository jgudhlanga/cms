<?php

declare(strict_types=1);

namespace App\Support\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;

/**
 * Maps the student status shown on the profile header onto the admissions
 * workflow steps stored against student applications.
 */
class StudentApplicationStatusMapper
{
    /**
     * @return list<string>
     */
    public function allowedSlugs(): array
    {
        return array_map(
            static fn (WorkflowStepEnum $step): string => $step->slug(),
            WorkflowStepEnum::cases(),
        );
    }

    /**
     * @return list<array{slug: string, name: string, description: string}>
     */
    public function options(): array
    {
        return array_map(
            static fn (WorkflowStepEnum $step): array => [
                'slug' => $step->slug(),
                'name' => $step->name(),
                'description' => $step->description(),
            ],
            WorkflowStepEnum::cases(),
        );
    }

    public function stepEnumBySlug(string $slug): ?WorkflowStepEnum
    {
        foreach (WorkflowStepEnum::cases() as $step) {
            if ($step->slug() === $slug) {
                return $step;
            }
        }

        return null;
    }

    public function workflowStepBySlug(string $slug): ?WorkflowStep
    {
        $step = $this->stepEnumBySlug($slug);

        if (! $step instanceof WorkflowStepEnum) {
            return null;
        }

        return WorkflowStep::query()
            ->where('slug', $step->slug())
            ->first();
    }

    public function isRejected(string $slug): bool
    {
        return $slug === WorkflowStepEnum::REJECTED->slug();
    }

    /**
     * Class list type an application should carry once it reaches the given status.
     */
    public function classListTypeForSlug(string $slug): ?ClassListTypeEnum
    {
        return match ($this->stepEnumBySlug($slug)) {
            WorkflowStepEnum::REQUIREMENTS => ClassListTypeEnum::PROVISIONAL,
            WorkflowStepEnum::WAITLISTED => ClassListTypeEnum::WAITING,
            WorkflowStepEnum::ACCEPTED => ClassListTypeEnum::VERIFIED,
            WorkflowStepEnum::ENROLLED => ClassListTypeEnum::FINAL,
            WorkflowStepEnum::REJECTED => ClassListTypeEnum::FAILED,
            default => null,
        };
    }

    /**
     * An application cannot be marked as enrolled while its level is missing.
     */
    public function requiresLevel(string $slug): bool
    {
        return $slug === WorkflowStepEnum::ENROLLED->slug();
    }

    public function isApplicationMissingLevel(StudentApplication $application): bool
    {
        $application->loadMissing('departmentLevel.level');

        if ($application->department_level_id === null) {
            return true;
        }

        return trim((string) $application->departmentLevel?->level?->name) === '';
    }
}
