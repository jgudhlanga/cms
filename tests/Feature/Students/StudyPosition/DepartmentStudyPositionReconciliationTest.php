<?php

declare(strict_types=1);

use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use Spatie\Activitylog\Models\Activity;

const DEPT_STUDY_POSITION_PERMISSIONS = ['view:department-metadata', 'update:department-metadata', 'confirm-study-position:students'];

function departmentStudyPositionManager(int $tenantId): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $user->givePermissionTo(DEPT_STUDY_POSITION_PERMISSIONS);
    test()->actingAs($user);

    return $user;
}

it('lists unconfirmed enrolments for the department scoped to the current period', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $unconfirmed = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    departmentStudyPositionManager((int) $context['tenantId']);

    $expectedPhase = App\Support\Institution\ProgrammeSemesterNameFormatter::qualifiedName(
        $offering['departmentLevel']->level->name,
        $y1s1->name,
    );

    $this->getJson(route('department-data-reconciliation.study-position.list', [
        'department' => $context['institutionDepartment']->id,
        'state' => 'unconfirmed',
    ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('rows.0.enrolmentId', (int) $unconfirmed->id)
        ->assertJsonPath('rows.0.systemPhase', $expectedPhase)
        ->assertJsonPath('rows.0.canConfirmOnRecord', true);
});

it('forbids a user without the confirm-study-position permission from listing or confirming', function (): void {
    $context = makeStudyPositionContext();
    $viewer = User::factory()->create(['tenant_id' => $context['tenantId']]);
    $viewer->givePermissionTo(['view:department-metadata', 'update:department-metadata']);
    test()->actingAs($viewer);

    $this->getJson(route('department-data-reconciliation.study-position.list', [
        'department' => $context['institutionDepartment']->id,
        'state' => 'unconfirmed',
    ]))->assertForbidden();

    $this->postJson(route('department-data-reconciliation.study-position.confirm', [
        'department' => $context['institutionDepartment']->id,
    ]), ['enrolment_ids' => [1], 'reason' => 'Checked against register'])
        ->assertForbidden();
});

it('forbids a department scoped user from reconciling a department they are not assigned to', function (): void {
    $context = makeStudyPositionContext();
    $outsider = actingAsDepartmentScopedUser((int) $context['tenantId'], $context['institutionDepartment']);
    $outsider->givePermissionTo('confirm-study-position:students');

    $this->getJson(route('department-data-reconciliation.study-position.list', [
        'department' => $context['otherInstitutionDepartment']->id,
        'state' => 'unconfirmed',
    ]))->assertForbidden();
});

it('bulk confirms selected enrolments using the phase on record and audits the actor', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    // The common shape: the sync observer already holds a row in the current (Semester 2) slot.
    $bothSemesters = [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ];
    $first = makeEnrolmentWithPhases($context, $offering, $bothSemesters);
    $second = makeEnrolmentWithPhases($context, $offering, $bothSemesters);

    $manager = departmentStudyPositionManager((int) $context['tenantId']);

    $this->postJson(route('department-data-reconciliation.study-position.confirm', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'enrolment_ids' => [$first->id, $second->id],
        'reason' => 'Confirmed against the printed class register',
    ])
        ->assertOk()
        ->assertJsonPath('summary.confirmed', 2)
        ->assertJsonPath('summary.skipped', 0);

    $confirmations = StudentStudyPositionConfirmation::query()->get()->keyBy('student_enrolment_id');

    expect($confirmations)->toHaveCount(2)
        ->and($confirmations[$first->id]->source)->toBe(StudyPositionSourceEnum::ADMIN)
        ->and($confirmations[$first->id]->sync_status)->toBe(StudyPositionSyncStatusEnum::UNCHANGED)
        ->and((int) $confirmations[$first->id]->confirmed_by)->toBe((int) $manager->id);

    $activity = Activity::query()->where('event', 'study-position-confirmed')->latest('id')->first();
    expect($activity->getExtraProperty('reason'))->toBe('Confirmed against the printed class register');
});

it('skips an enrolment with no phase on record instead of failing the whole batch', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $noPhase = makeEnrolmentWithPhases($context, $offering, []);
    $withPhase = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ]);

    departmentStudyPositionManager((int) $context['tenantId']);

    $this->postJson(route('department-data-reconciliation.study-position.confirm', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'enrolment_ids' => [$noPhase->id, $withPhase->id],
        'reason' => 'Bulk confirming department register',
    ])
        ->assertOk()
        ->assertJsonPath('summary.confirmed', 1)
        ->assertJsonPath('summary.skipped', 1)
        ->assertJsonPath('rows.0.status', 'skipped');

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(1);
});

it('skips, with a clear reason, a student stuck on a phase pinned to an earlier slot', function (): void {
    // About 4% of real "not confirmed" enrolments look like this: the phase has not advanced,
    // so its only row still sits in last period's slot. The write guards refuse to pin the same
    // phase into two slots, so the bulk tool must report it rather than silently duplicating it.
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $stuck = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);

    departmentStudyPositionManager((int) $context['tenantId']);

    $this->postJson(route('department-data-reconciliation.study-position.confirm', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'enrolment_ids' => [$stuck->id],
        'reason' => 'Attempting to confirm a stuck phase',
    ])
        ->assertOk()
        ->assertJsonPath('summary.confirmed', 0)
        ->assertJsonPath('summary.skipped', 1)
        ->assertJsonPath('rows.0.status', 'skipped')
        ->assertJsonPath('rows.0.reason', fn (string $reason) => str_contains($reason, 'already has another semester record'));

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('refuses to confirm an enrolment belonging to another department', function (): void {
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1] = $offering['phases'];

    $other = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $y1s1],
    ], statusId: null);
    $other->update(['institution_department_id' => $context['otherInstitutionDepartment']->id]);

    departmentStudyPositionManager((int) $context['tenantId']);

    $this->postJson(route('department-data-reconciliation.study-position.confirm', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'enrolment_ids' => [$other->id],
        'reason' => 'Attempting cross department confirm',
    ])
        ->assertOk()
        ->assertJsonPath('summary.confirmed', 0)
        ->assertJsonPath('rows.0.status', 'skipped');

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});
