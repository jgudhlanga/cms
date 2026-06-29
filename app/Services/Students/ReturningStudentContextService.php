<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\PaymentHelper;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Support\Collection;

class ReturningStudentContextService
{
    public function __construct(
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function canStartApplication(Student $student): bool
    {
        if ($this->hasActiveEnrolment($student)) {
            return false;
        }

        if ($this->hasApplicationInOpenIntake($student)) {
            return false;
        }

        return $this->openIntakes()->isNotEmpty();
    }

    public function needsContinueInClassPage(Student $student): bool
    {
        if (! $this->canContinueInClass($student)) {
            return false;
        }

        foreach ($this->openIntakes() as $intake) {
            $meta = $student->meta_data['returning_student'] ?? null;

            if (
                is_array($meta)
                && ($meta['path'] ?? null) === 'continuation'
                && (int) ($meta['intake_period_id'] ?? 0) === (int) $intake->id
                && isset($meta['acknowledged_at'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationHubFor(Student $student, User $user): array
    {
        $openIntakes = $this->openIntakes();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $intakePeriod = $applicationFee?->intakePeriod ?? $openIntakes->first();
        $hasPaid = $intakePeriod !== null
            && PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod);

        return [
            'openIntakes' => IntakePeriodResource::collection($openIntakes),
            'canStartApplication' => $this->canStartApplication($student),
            'hasPaidApplicationFee' => $hasPaid,
            'paidLevelId' => $applicationFee?->level_id,
            'paidLevelName' => $applicationFee?->level?->name,
            'hasReapplyAcknowledgement' => $intakePeriod !== null
                && $this->hasReapplyAcknowledgementForIntake($student, $intakePeriod),
            'canContinueInClass' => $this->canContinueInClass($student),
            'continueInClassUrl' => route('portal.returning-student.continue.show'),
            'requiresIntakeSelection' => $openIntakes->count() > 1,
        ];
    }

    public function needsOnboarding(Student $student): bool
    {
        if ($this->hasActiveEnrolment($student)) {
            return false;
        }

        if ($this->hasApplicationInOpenIntake($student)) {
            return false;
        }

        $openIntakes = $this->applicationFeeService->openIntakePeriodsForPortal();

        if ($openIntakes->isEmpty()) {
            return false;
        }

        return ! $this->hasAcknowledgedForOpenIntakes($student, $openIntakes);
    }

    public function canContinueInClass(Student $student): bool
    {
        if ($student->student_number === null || $student->student_number === '') {
            return false;
        }

        return $this->qualifyingApplicationForContinuation($student) !== null;
    }

    public function qualifyingApplicationForContinuation(Student $student): ?StudentApplication
    {
        return $student->applications()
            ->whereNull('student_applications.deleted_at')
            ->whereHas('departmentWorkflowStep.workflowStep', function ($query): void {
                $query->where('slug', WorkflowStepEnum::ACCEPTED->slug());
            })
            ->whereHas('classList', function ($query): void {
                $query->where('type', ClassListTypeEnum::VERIFIED->value);
            })
            ->latest('student_applications.id')
            ->first();
    }

    /**
     * @return Collection<int, IntakePeriod>
     */
    public function openIntakes(): Collection
    {
        return $this->applicationFeeService->openIntakePeriodsForPortal();
    }

    public function hasAcknowledgedForIntake(Student $student, IntakePeriod $intake): bool
    {
        $meta = $student->meta_data['returning_student'] ?? null;

        if (! is_array($meta)) {
            return false;
        }

        return (int) ($meta['intake_period_id'] ?? 0) === (int) $intake->id
            && isset($meta['acknowledged_at'], $meta['path']);
    }

    public function hasReapplyAcknowledgementForIntake(Student $student, IntakePeriod $intake): bool
    {
        $meta = $student->meta_data['returning_student'] ?? null;

        if (! is_array($meta)) {
            return false;
        }

        return ($meta['path'] ?? null) === 'reapply'
            && (int) ($meta['intake_period_id'] ?? 0) === (int) $intake->id
            && isset($meta['acknowledged_at']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(Student $student): array
    {
        $openIntakes = $this->openIntakes();

        return [
            'needsContinueInClassPage' => $this->needsContinueInClassPage($student),
            'canStartApplication' => $this->canStartApplication($student),
            'canContinueInClass' => $this->canContinueInClass($student),
            'openIntakeIds' => $openIntakes->pluck('id')->values()->all(),
            'openIntakeNames' => $openIntakes->pluck('name')->values()->all(),
            'hasReapplyAcknowledgement' => $openIntakes->contains(
                fn (IntakePeriod $intake): bool => $this->hasReapplyAcknowledgementForIntake($student, $intake)
            ),
        ];
    }

    public function persistAcknowledgement(
        Student $student,
        string $path,
        IntakePeriod $intake,
        ?int $studentApplicationId = null,
    ): void {
        $meta = $student->meta_data ?? [];
        $meta['returning_student'] = [
            'path' => $path,
            'acknowledged_at' => now()->toIso8601String(),
            'intake_period_id' => $intake->id,
            'student_application_id' => $studentApplicationId,
            'source' => $path === 'continuation' ? 'portal_continuation' : 'portal_reapply',
        ];

        $student->update(['meta_data' => $meta]);
    }

    private function hasActiveEnrolment(Student $student): bool
    {
        return $student->enrolments()
            ->whereHas('studentEnrolmentStatus', fn ($query) => $query->where('slug', 'active'))
            ->exists();
    }

    private function hasApplicationInOpenIntake(Student $student): bool
    {
        $openIntakeIds = $this->openIntakes()->pluck('id');

        if ($openIntakeIds->isEmpty()) {
            return false;
        }

        return $student->applications()
            ->whereIn('intake_period_id', $openIntakeIds)
            ->exists();
    }

    /**
     * @param  Collection<int, IntakePeriod>  $openIntakes
     */
    private function hasAcknowledgedForOpenIntakes(Student $student, Collection $openIntakes): bool
    {
        foreach ($openIntakes as $intake) {
            if ($this->hasAcknowledgedForIntake($student, $intake)) {
                return true;
            }
        }

        return false;
    }
}
