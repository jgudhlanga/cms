<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
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

test('dashboard returns department distribution for academic departments', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $program = createVerifiedStudentApplication('DASH-DEPT-DIST-01');
    $program->institutionDepartment->department->update(['is_academic' => true]);
    $program->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($program, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('departmentDistribution', 1)
            ->where('departmentDistribution.0.applicationCount', 1)
            ->where('departmentDistribution.0.institutionDepartmentId', $program->institution_department_id)
            ->where('enrolmentSummary.applications', 1)
        );
});

test('dashboard department distribution totals align with enrolment summary applications', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    foreach (['DASH-DEPT-A', 'DASH-DEPT-B', 'DASH-DEPT-C'] as $studentNumber) {
        $program = createVerifiedStudentApplication($studentNumber);
        $program->institutionDepartment->department->update(['is_academic' => true]);
        $program->update([
            'intake_period_id' => $intakePeriod->id,
            'tenant_id' => $user->tenant_id,
            'department_application_step_id' => resolveDepartmentApplicationStep($program, WorkflowStepEnum::REVIEW)->id,
        ]);
    }

    $response = $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful();

    $props = $response->original->getData()['page']['props'];
    $departmentTotal = collect($props['departmentDistribution'])->sum('applicationCount');

    expect($departmentTotal)->toBe($props['enrolmentSummary']['applications']);
    expect($departmentTotal)->toBe(3);
});

test('dashboard department distribution excludes soft deleted applications', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $activeProgram = createVerifiedStudentApplication('DASH-DEPT-ACTIVE');
    $activeProgram->institutionDepartment->department->update(['is_academic' => true]);
    $activeProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($activeProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $deletedProgram = createVerifiedStudentApplication('DASH-DEPT-DELETED');
    $deletedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $deletedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($deletedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);
    StudentApplication::query()->whereKey($deletedProgram->id)->delete();

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('departmentDistribution', 1)
            ->where('departmentDistribution.0.applicationCount', 1)
            ->where('enrolmentSummary.applications', 1)
        );
});

test('dashboard department distribution excludes non academic departments', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $program = createVerifiedStudentApplication('DASH-NON-ACAD');
    $program->institutionDepartment->department->update(['is_academic' => false]);
    $program->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($program, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('departmentDistribution', 1)
            ->where('departmentDistribution.0.departmentName', __('trans.ui_unassigned'))
            ->where('departmentDistribution.0.applicationCount', 1)
            ->where('enrolmentSummary.applications', 1)
        );
});

test('dashboard enrolment metrics ignore academic calendar id in request', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);
    $calendar = seedDashboardAcademicCalendar();

    $program = createVerifiedStudentApplication('DASH-CAL-IGNORE');
    $program->institutionDepartment->department->update(['is_academic' => true]);
    $program->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($program, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $otherCalendar = AcademicCalendar::query()->create([
        'calendar_year' => (string) (now()->year - 1),
        'type' => $calendar->type,
        'opening_date' => now()->subYears(2)->startOfYear()->toDateString(),
        'closing_date' => now()->subYears(2)->endOfYear()->toDateString(),
    ]);

    $withoutCalendar = $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    $withOtherCalendar = $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id.'&academic_calendar_id='.$otherCalendar->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    expect($withOtherCalendar['enrolmentSummary'])->toBe($withoutCalendar['enrolmentSummary']);
    expect($withOtherCalendar['departmentDistribution'])->toBe($withoutCalendar['departmentDistribution']);
});

test('dashboard department distribution is scoped to selected intake period', function () {
    $user = userWithDashboardPermission();
    $selectedIntake = seedDashboardIntakePeriod($user->tenant_id);

    $otherIntake = IntakePeriod::withoutGlobalScopes()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Other Intake Dept Dist 2025',
        'start_date' => now()->subMonths(6)->startOfMonth()->toDateString(),
        'end_date' => now()->subMonths(4)->endOfMonth()->toDateString(),
        'calendar_year' => '2024/2025',
        'is_active' => true,
    ]);

    $selectedProgram = createVerifiedStudentApplication('DASH-DEPT-SELECTED-01');
    $selectedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $selectedProgram->update([
        'intake_period_id' => $selectedIntake->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($selectedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $selectedProgramTwo = createVerifiedStudentApplication('DASH-DEPT-SELECTED-02');
    $selectedProgramTwo->institutionDepartment->department->update(['is_academic' => true]);
    $selectedProgramTwo->update([
        'intake_period_id' => $selectedIntake->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($selectedProgramTwo, WorkflowStepEnum::REVIEW)->id,
    ]);

    $otherProgram = createVerifiedStudentApplication('DASH-DEPT-OTHER-01');
    $otherProgram->institutionDepartment->department->update(['is_academic' => true]);
    $otherProgram->update([
        'intake_period_id' => $otherIntake->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($otherProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$selectedIntake->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('enrolmentSummary.applications', 2)
        );

    $selectedProps = $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$selectedIntake->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    $otherProps = $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$otherIntake->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    expect(collect($selectedProps['departmentDistribution'])->sum('applicationCount'))->toBe(2);
    expect(collect($otherProps['departmentDistribution'])->sum('applicationCount'))->toBe(1);
    expect($otherProps['enrolmentSummary']['applications'])->toBe(1);
    expect($selectedProps['departmentDistribution'])->not->toBe($otherProps['departmentDistribution']);
});

test('dashboard department distribution includes unassigned applications without academic department link', function () {
    $user = userWithDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $linkedProgram = createVerifiedStudentApplication('DASH-LINKED');
    $linkedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $linkedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($linkedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $unlinkedProgram = createVerifiedStudentApplication('DASH-UNLINKED');
    $unlinkedProgram->institutionDepartment->department->update(['is_academic' => false]);
    $unlinkedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'department_application_step_id' => resolveDepartmentApplicationStep($unlinkedProgram, WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('departmentDistribution', 2)
            ->where('enrolmentSummary.applications', 2)
            ->where('departmentDistribution.1.departmentName', __('trans.ui_unassigned'))
            ->where('departmentDistribution.1.applicationCount', 1)
            ->where('departmentDistribution.1.institutionDepartmentId', 0)
        );
});
