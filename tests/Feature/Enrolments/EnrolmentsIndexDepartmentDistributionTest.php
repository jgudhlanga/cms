<?php

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Rbac\Permission;
use App\Models\Users\User;

test('enrolments index returns department distribution for selected intake period', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('view:student-applications', 'web');
    $user->givePermissionTo('view:student-applications');

    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $program = createVerifiedStudentApplication('ENROL-DEPT-DIST-01');
    $program->institutionDepartment->department->update(['is_academic' => true]);
    $program->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
        'workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $this->actingAs($user)
        ->get('/enrolments?intake_period_id='.$intakePeriod->id)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('enrolments/Index')
            ->has('departmentDistribution', 1)
            ->where('departmentDistribution.0.applicationCount', 1)
            ->where('departmentDistribution.0.institutionDepartmentId', $program->institution_department_id)
            ->where('intakePeriod.id', $intakePeriod->id)
        );
});

test('enrolments index department distribution is scoped to selected intake period', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('view:student-applications', 'web');
    $user->givePermissionTo('view:student-applications');

    $selectedIntake = seedDashboardIntakePeriod($user->tenant_id);

    $otherIntake = IntakePeriod::withoutGlobalScopes()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Other Enrolments Intake '.uniqid(),
        'start_date' => now()->subMonths(6)->startOfMonth()->toDateString(),
        'end_date' => now()->subMonths(4)->endOfMonth()->toDateString(),
        'calendar_year' => '2024/2025',
        'is_active' => true,
    ]);

    $selectedProgram = createVerifiedStudentApplication('ENROL-DEPT-SELECTED');
    $selectedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $selectedProgram->update([
        'intake_period_id' => $selectedIntake->id,
        'tenant_id' => $user->tenant_id,
        'workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::ACCEPTED)->id,
    ]);

    $otherProgram = createVerifiedStudentApplication('ENROL-DEPT-OTHER');
    $otherProgram->institutionDepartment->department->update(['is_academic' => true]);
    $otherProgram->update([
        'intake_period_id' => $otherIntake->id,
        'tenant_id' => $user->tenant_id,
        'workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::REVIEW)->id,
    ]);

    $selectedProps = $this->actingAs($user)
        ->get('/enrolments?intake_period_id='.$selectedIntake->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    $otherProps = $this->actingAs($user)
        ->get('/enrolments?intake_period_id='.$otherIntake->id)
        ->assertSuccessful()
        ->original->getData()['page']['props'];

    expect(collect($selectedProps['departmentDistribution'])->sum('applicationCount'))->toBe(1);
    expect(collect($otherProps['departmentDistribution'])->sum('applicationCount'))->toBe(1);
    expect($selectedProps['departmentDistribution'])->not->toBe($otherProps['departmentDistribution']);
});
