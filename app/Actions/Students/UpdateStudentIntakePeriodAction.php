<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Finance\PastelLinkedStudent;
use App\Models\Institution\IntakePeriod;
use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudentIntakePeriodAction
{
    public function execute(Student $student, int $intakePeriodId, string $reason, ?User $actor = null): Student
    {
        $application = $this->resolveHeaderApplication($student);

        if (! $application instanceof StudentApplication) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('students.change_intake_period_no_application'),
            ]);
        }

        $previousIntakePeriodId = (int) $application->intake_period_id;

        if ($previousIntakePeriodId === $intakePeriodId) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('students.change_intake_period_unchanged'),
            ]);
        }

        $newIntakePeriod = IntakePeriod::query()->find($intakePeriodId);

        if (! $newIntakePeriod instanceof IntakePeriod) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('students.change_intake_period_invalid'),
            ]);
        }

        $application->loadMissing(['intakePeriod', 'student.user']);
        $previousIntakePeriod = $application->intakePeriod;
        $userId = (int) ($application->student?->user_id ?? 0);

        $this->assertApplicationFeeCanMove($application, $userId, $previousIntakePeriodId, $intakePeriodId);

        DB::transaction(function () use (
            $application,
            $intakePeriodId,
            $previousIntakePeriodId,
            $userId,
            $student,
        ): void {
            $application->update(['intake_period_id' => $intakePeriodId]);

            $this->moveApplicationFees($application, $userId, $previousIntakePeriodId, $intakePeriodId);
            $this->moveLedgers($application, $intakePeriodId);
            $this->movePastelLink($student, $previousIntakePeriodId, $intakePeriodId);
        });

        $this->audit(
            $student,
            $actor,
            $application,
            $previousIntakePeriod,
            $newIntakePeriod,
            $reason,
        );

        return $student->refresh();
    }

    private function resolveHeaderApplication(Student $student): ?StudentApplication
    {
        $student->loadMissing([
            'latestEnrolment.studentApplication.intakePeriod',
            'latestApplication.intakePeriod',
        ]);

        $enrolment = $student->latestEnrolment;

        if ($enrolment instanceof StudentEnrolment) {
            $linked = $enrolment->studentApplication;

            if ($linked instanceof StudentApplication) {
                return $linked;
            }
        }

        $latest = $student->latestApplication;

        return $latest instanceof StudentApplication ? $latest : null;
    }

    private function assertApplicationFeeCanMove(
        StudentApplication $application,
        int $userId,
        int $previousIntakePeriodId,
        int $newIntakePeriodId,
    ): void {
        if ($userId < 1) {
            return;
        }

        $feeOnApplication = ApplicationFee::query()
            ->where('student_application_id', $application->id)
            ->exists();

        $sharedFeeOnOldIntake = ApplicationFee::query()
            ->where('user_id', $userId)
            ->where('intake_period_id', $previousIntakePeriodId)
            ->where(function ($query) use ($application): void {
                $query->whereNull('student_application_id')
                    ->orWhere('student_application_id', $application->id);
            })
            ->exists();

        if (! $feeOnApplication && ! $sharedFeeOnOldIntake) {
            return;
        }

        $siblingStillOnOldIntake = StudentApplication::query()
            ->where('student_id', $application->student_id)
            ->whereKeyNot($application->getKey())
            ->where('intake_period_id', $previousIntakePeriodId)
            ->exists();

        // When siblings remain on the old intake, we leave the shared user+intake fee alone.
        // Only fees explicitly linked to this application are candidates to move — and those
        // still collide with any existing fee on the target intake for the same user.
        $willMoveAnyFee = $feeOnApplication
            || ($sharedFeeOnOldIntake && ! $siblingStillOnOldIntake);

        if (! $willMoveAnyFee) {
            return;
        }

        $conflict = ApplicationFee::query()
            ->where('user_id', $userId)
            ->where('intake_period_id', $newIntakePeriodId)
            ->where(function ($query) use ($application): void {
                $query->whereNull('student_application_id')
                    ->orWhere('student_application_id', '!=', $application->id);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('students.change_intake_period_fee_conflict'),
            ]);
        }
    }

    private function moveApplicationFees(
        StudentApplication $application,
        int $userId,
        int $previousIntakePeriodId,
        int $newIntakePeriodId,
    ): void {
        ApplicationFee::query()
            ->where('student_application_id', $application->id)
            ->update(['intake_period_id' => $newIntakePeriodId]);

        if ($userId < 1) {
            return;
        }

        $siblingStillOnOldIntake = StudentApplication::query()
            ->where('student_id', $application->student_id)
            ->whereKeyNot($application->getKey())
            ->where('intake_period_id', $previousIntakePeriodId)
            ->exists();

        if ($siblingStillOnOldIntake) {
            return;
        }

        // Move the shared user+intake fee only when no other application still needs it.
        ApplicationFee::query()
            ->where('user_id', $userId)
            ->where('intake_period_id', $previousIntakePeriodId)
            ->where(function ($query) use ($application): void {
                $query->whereNull('student_application_id')
                    ->orWhere('student_application_id', $application->id);
            })
            ->update(['intake_period_id' => $newIntakePeriodId]);
    }

    private function moveLedgers(StudentApplication $application, int $newIntakePeriodId): void
    {
        Ledger::query()
            ->where('student_application_id', $application->id)
            ->update(['intake_period_id' => $newIntakePeriodId]);
    }

    private function movePastelLink(Student $student, int $previousIntakePeriodId, int $newIntakePeriodId): void
    {
        PastelLinkedStudent::query()
            ->where('student_id', $student->id)
            ->where('intake_period_id', $previousIntakePeriodId)
            ->update(['intake_period_id' => $newIntakePeriodId]);
    }

    private function audit(
        Student $student,
        ?User $actor,
        StudentApplication $application,
        ?IntakePeriod $previousIntakePeriod,
        IntakePeriod $newIntakePeriod,
        string $reason,
    ): void {
        $logger = activity('Student')
            ->performedOn($student)
            ->event('intake-period-changed')
            ->withProperties([
                'student_application_id' => $application->id,
                'old_intake_period_id' => $previousIntakePeriod?->id,
                'old_intake_period' => $previousIntakePeriod?->name,
                'new_intake_period_id' => $newIntakePeriod->id,
                'new_intake_period' => $newIntakePeriod->name,
                'reason' => $reason,
            ]);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log(__('students.change_intake_period_activity_description', [
            'intake' => $newIntakePeriod->name,
        ]));
    }
}
