<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class NonEnrolledStudentUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'maintenance-non-enrolled-student-user',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->full_name,
                'email' => $this->email,
                'phoneNumber' => $this->phone_number,
                'lastLoginAt' => $this->last_login_at,
                'createdAt' => $this->created_at,
                'hasStudentProfile' => $this->has_student_profile,
                'studentId' => $this->studentProfile?->id,
                'studentNumber' => $this->studentProfile?->student_number,
                'applicationStatusSummary' => $this->resolveApplicationStatusSummary(),
                'roles' => $this->roles->map(static fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()->all(),
            ],
        ];
    }

    private function resolveApplicationStatusSummary(): string
    {
        if ($this->studentProfile === null) {
            return __('trans.maintenance_users_status_no_profile');
        }

        $programs = $this->studentProfile->applications;

        if ($programs->isEmpty()) {
            return __('trans.maintenance_users_status_no_programmes');
        }

        $statuses = $programs
            ->map(fn (StudentApplication $program): ?string => $this->resolveProgramStatus($program))
            ->filter()
            ->unique()
            ->values();

        if ($statuses->isEmpty()) {
            return __('trans.maintenance_users_status_unknown');
        }

        return $statuses->implode(', ');
    }

    private function resolveProgramStatus(StudentApplication $program): ?string
    {
        $classListType = $program->classList?->type;
        $verifiedType = $classListType instanceof ClassListTypeEnum
            ? $classListType
            : ClassListTypeEnum::tryFrom((string) $classListType);

        if ($verifiedType === ClassListTypeEnum::VERIFIED) {
            return __('trans.maintenance_users_status_verified');
        }

        $workflowSlug = $program->departmentWorkflowStep?->workflowStep?->slug;

        return match ($workflowSlug) {
            WorkflowStepEnum::REVIEW->slug() => __('trans.maintenance_users_status_review'),
            WorkflowStepEnum::WAITLISTED->slug() => __('trans.maintenance_users_status_waitlisted'),
            default => null,
        };
    }
}
