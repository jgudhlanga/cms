<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\StudentApplication;

beforeEach(function () {
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

test('dashboard returns enrolment summary metrics for selected intake period', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $reviewProgram = createVerifiedStudentApplication('DASH-REVIEW-01');
    $reviewProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($reviewProgram, WorkflowStepEnum::REVIEW)->id,
    ]);

    $acceptedProgram = createVerifiedStudentApplication('DASH-ACCEPTED-01');
    $acceptedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($acceptedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $enrolledProgram = createVerifiedStudentApplication('DASH-ENROLLED-01');
    $enrolledProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($enrolledProgram, WorkflowStepEnum::ENROLLED)->id,
    ]);

    $waitlistedProgram = createVerifiedStudentApplication('DASH-WAITLISTED-01');
    $waitlistedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($waitlistedProgram, WorkflowStepEnum::WAITLISTED)->id,
    ]);

    $confirmedProgram = createVerifiedStudentApplication('DASH-CONFIRMED-01');
    $confirmedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($confirmedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);
    ClassList::query()
        ->where('student_application_id', $confirmedProgram->id)
        ->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [
                'identity_confirmed' => true,
                'disability_confirmed' => true,
                'names_confirmed' => true,
            ],
        ]);

    $provisionalOnlyProgram = createVerifiedStudentApplication('DASH-PROVISIONAL-01');
    $provisionalOnlyProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($provisionalOnlyProgram, WorkflowStepEnum::REVIEW)->id,
    ]);
    ClassList::query()
        ->where('student_application_id', $provisionalOnlyProgram->id)
        ->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [
                'identity_confirmed' => false,
                'disability_confirmed' => false,
                'names_confirmed' => false,
            ],
        ]);

    $failedProgram = createVerifiedStudentApplication('DASH-FAILED-01');
    $failedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($failedProgram, WorkflowStepEnum::REVIEW)->id,
    ]);
    ClassList::query()
        ->where('student_application_id', $failedProgram->id)
        ->update([
            'type' => ClassListTypeEnum::FAILED->value,
        ]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('enrolmentSummary.applications', 7)
            ->where('enrolmentSummary.offersMade', 3)
            ->where('enrolmentSummary.confirmed', 1)
            ->where('enrolmentSummary.waitlisted', 1)
            ->where('enrolmentSummary.provisional', 2)
            ->where('enrolmentSummary.failedRejected', 1)
        );
});

test('dashboard enrolment summary metrics are scoped to selected intake period', function () {
    $user = userWithDashboardPermission();
    $selectedIntake = seedDashboardIntakePeriod($user->tenant_id);

    $otherIntake = IntakePeriod::withoutGlobalScopes()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Other Intake 2025',
        'start_date' => now()->subMonths(6)->startOfMonth()->toDateString(),
        'end_date' => now()->subMonths(4)->endOfMonth()->toDateString(),
        'calendar_year' => '2024/2025',
        'is_active' => true,
    ]);

    $selectedProgram = createVerifiedStudentApplication('DASH-SELECTED-01');
    $selectedProgram->update([
        'intake_period_id' => $selectedIntake->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($selectedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $otherProgram = createVerifiedStudentApplication('DASH-OTHER-01');
    $otherProgram->update([
        'intake_period_id' => $otherIntake->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($otherProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $selectedProvisionalProgram = createVerifiedStudentApplication('DASH-SELECTED-PROV-01');
    $selectedProvisionalProgram->update([
        'intake_period_id' => $selectedIntake->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($selectedProvisionalProgram, WorkflowStepEnum::REVIEW)->id,
    ]);
    ClassList::query()
        ->where('student_application_id', $selectedProvisionalProgram->id)
        ->update(['type' => ClassListTypeEnum::PROVISIONAL->value]);

    $otherFailedProgram = createVerifiedStudentApplication('DASH-OTHER-FAILED-01');
    $otherFailedProgram->update([
        'intake_period_id' => $otherIntake->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($otherFailedProgram, WorkflowStepEnum::REVIEW)->id,
    ]);
    ClassList::query()
        ->where('student_application_id', $otherFailedProgram->id)
        ->update(['type' => ClassListTypeEnum::FAILED->value]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$selectedIntake->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrolmentSummary.applications', 2)
            ->where('enrolmentSummary.offersMade', 1)
            ->where('enrolmentSummary.provisional', 1)
            ->where('enrolmentSummary.failedRejected', 0)
        );

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$otherIntake->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrolmentSummary.applications', 2)
            ->where('enrolmentSummary.offersMade', 1)
            ->where('enrolmentSummary.provisional', 0)
            ->where('enrolmentSummary.failedRejected', 1)
        );
});

test('dashboard enrolment summary metrics ignore soft deleted student programs', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $activeProgram = createVerifiedStudentApplication('DASH-ACTIVE-01');
    $activeProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($activeProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $deletedProgram = createVerifiedStudentApplication('DASH-DELETED-01');
    $deletedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'department_application_step_id' => resolveDepartmentApplicationStep($deletedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);
    StudentApplication::query()->whereKey($deletedProgram->id)->delete();

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrolmentSummary.applications', 1)
            ->where('enrolmentSummary.offersMade', 1)
        );
});
