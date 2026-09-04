<?php

declare(strict_types=1);

namespace App\Actions\Enrolments;

use App\Actions\Students\UpsertYearStudentEnrolmentAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\EnrolmentHelper;
use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\Enrolments\ClassList;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncStudentApplicationClassListLifecycleAction
{
    public function __construct(
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
    ) {}

    public function sync(ClassList $entry, ClassListTypeEnum $toType, bool $rejectOtherApplications = true): void
    {
        $application = $this->application($entry);

        match ($toType) {
            ClassListTypeEnum::PROVISIONAL => $this->syncListed($entry, $application, $toType, WorkflowStepEnum::REQUIREMENTS),
            ClassListTypeEnum::WAITING => $this->syncListed($entry, $application, $toType, WorkflowStepEnum::WAITLISTED),
            ClassListTypeEnum::FAILED => $this->syncListed($entry, $application, $toType, WorkflowStepEnum::REJECTED),
            ClassListTypeEnum::VERIFIED => $this->syncVerified($application, $rejectOtherApplications),
            ClassListTypeEnum::FINAL => $this->syncFinal($application),
        };
    }

    public function syncApplicationToType(
        StudentApplication $application,
        ClassListTypeEnum $toType,
        bool $rejectOtherApplications = true,
    ): void {
        $entry = $application->classList;

        if (! $entry instanceof ClassList) {
            $entry = ClassList::query()->create([
                'student_application_id' => $application->id,
                'type' => $toType->value,
                'attributes' => $this->defaultClassListAttributes(),
            ]);
            $application->setRelation('classList', $entry);
        } else {
            $entry->type = $toType->value;
            $entry->save();
        }

        $this->sync($entry, $toType, $rejectOtherApplications);
    }

    public function applyWorkflow(StudentApplication $application, WorkflowStepEnum $workflowStep): void
    {
        $this->setWorkflow($application, $workflowStep);
    }

    public function resetToReview(StudentApplication $application): void
    {
        $this->setWorkflow($application, WorkflowStepEnum::REVIEW);
    }

    private function syncListed(
        ClassList $entry,
        StudentApplication $application,
        ClassListTypeEnum $toType,
        WorkflowStepEnum $workflowStep,
    ): void {
        $this->setWorkflow($application, $workflowStep);
        $this->dispatchProgress($entry, $toType->value);
    }

    private function syncVerified(StudentApplication $application, bool $rejectOtherApplications): void
    {
        $this->ensureStudentNumber($application);
        $this->setWorkflow($application, WorkflowStepEnum::ACCEPTED);
        $this->sendOfferLetter($application);

        if (! $rejectOtherApplications) {
            return;
        }

        $application->loadMissing('student', 'departmentLevel.level');

        if ($application->student instanceof Student && EnrolmentHelper::isEntryLevel($application)) {
            EnrolmentHelper::rejectOtherApplications($application->student, $application);
        }
    }

    private function syncFinal(StudentApplication $application): void
    {
        $this->ensureStudentNumber($application);
        $this->setWorkflow($application, WorkflowStepEnum::ENROLLED);
        $this->upsertYearStudentEnrolment->execute($application);
    }

    private function application(ClassList $entry): StudentApplication
    {
        $entry->loadMissing(['studentApplication.student.user', 'studentApplication.departmentLevel.level', 'studentApplication.institutionDepartment', 'studentApplication.intakePeriod']);

        $application = $entry->studentApplication;
        if (! $application instanceof StudentApplication) {
            throw new RuntimeException("Student application was not found for class list id \"{$entry->id}\".");
        }

        return $application;
    }

    private function setWorkflow(StudentApplication $application, WorkflowStepEnum $workflowStep): void
    {
        $step = WorkflowStep::query()->where('slug', $workflowStep->slug())->first();
        if ($step === null) {
            throw new RuntimeException("Workflow step \"{$workflowStep->slug()}\" was not found.");
        }

        $application->update(['workflow_step_id' => $step->id]);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultClassListAttributes(): array
    {
        return [
            'identity_confirmed' => false,
            'disability_confirmed' => false,
            'names_confirmed' => false,
            'o_level_confirmed' => false,
            'previous_level_confirmed' => false,
            'read_write_confirmed' => false,
            'application_fee_confirmed' => false,
            'proof_of_payment_confirmed' => false,
            'passport_photos_confirmed' => false,
            'original_birth_certificate_confirmed' => false,
            'original_national_identity_confirmed' => false,
            'original_education_certificates_confirmed' => false,
        ];
    }

    private function ensureStudentNumber(StudentApplication $application): void
    {
        $student = $application->student;
        if (! $student instanceof Student) {
            return;
        }

        if (filled($student->student_number)) {
            return;
        }

        $student->update([
            'student_number' => EnrolmentHelper::resolveStudentNumber($application),
            'student_number_generated' => true,
        ]);
    }

    private function sendOfferLetter(StudentApplication $application): void
    {
        $user = $application->student?->user;
        if ($user === null || blank($user->email)) {
            return;
        }

        SendOfferLetterJob::dispatch($user->full_name, $user->email, (string) $application->id)->withoutDelay();
    }

    private function dispatchProgress(ClassList $entry, string $type): void
    {
        $details = DB::table('class_lists as cl')
            ->join('student_applications as sp', 'sp.id', '=', 'cl.student_application_id')
            ->join('institution_departments as idp', 'idp.id', '=', 'sp.institution_department_id')
            ->join('departments as dp', 'dp.id', '=', 'idp.department_id')
            ->join('department_levels as dl', 'dl.id', '=', 'sp.department_level_id')
            ->join('levels as lv', 'lv.id', '=', 'dl.level_id')
            ->join('department_courses as dc', 'dc.id', '=', 'sp.department_course_id')
            ->join('courses as cs', 'cs.id', '=', 'dc.course_id')
            ->where('cl.id', $entry->id)
            ->select([
                'sp.institution_department_id',
                'dp.name as department',
                'lv.name as level',
                'cs.name as course',
            ])
            ->first();

        if ($details === null) {
            return;
        }

        SendEnrolmentProgressJob::dispatch(
            $entry->id,
            $type,
            $details->institution_department_id,
            $details->department,
            $details->level,
            $details->course,
        )->withoutDelay();
    }
}
