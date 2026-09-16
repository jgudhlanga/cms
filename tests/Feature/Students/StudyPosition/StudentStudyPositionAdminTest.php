<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Role;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Support\Rbac\PermissionRegistry;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Spatie\Activitylog\Models\Activity;

const STUDY_POSITION_PERMISSION = 'confirm-study-position:students';

function studyPositionRegistryUser(int $tenantId): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $user->givePermissionTo(['viewAny:students', 'view:students', STUDY_POSITION_PERMISSION]);
    test()->actingAs($user);

    return $user;
}

/**
 * @return array{context: array<string, mixed>, offering: array<string, mixed>, enrolment: StudentEnrolment, student: Student}
 */
function makeAdminStudyPositionStudent(): array
{
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    $enrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);
    $student = Student::query()->findOrFail($enrolment->student_id);

    return compact('context', 'offering', 'enrolment', 'student');
}

/**
 * @return array<string, mixed>
 */
function studyPositionPayload(StudentEnrolment $enrolment, int $phaseId, string $reason = 'Confirmed against the class register'): array
{
    return [
        'positions' => [['student_enrolment_id' => $enrolment->id, 'programme_semester_id' => $phaseId]],
        'reason' => $reason,
    ];
}

it('registers the permission and grants it to registry and department heads', function (): void {
    expect(PermissionRegistry::allValues())->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::registrarPermissions())->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::registryOfficerPermissions())->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::hodPermissions())->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::headOfDivisionPermissions())->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::lecturerPermissions())->not->toContain(STUDY_POSITION_PERMISSION)
        ->and(PermissionHelper::portalPermissions())->not->toContain(STUDY_POSITION_PERMISSION);

    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();

    foreach ([RoleEnum::SUPER_USER, RoleEnum::REGISTRAR, RoleEnum::HEAD_OF_DEPARTMENT] as $role) {
        expect(Role::query()->where('name', $role->name())->firstOrFail()->permissions->pluck('name')->all())
            ->toContain(STUDY_POSITION_PERMISSION);
    }

    expect(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail()->permissions->pluck('name')->all())
        ->not->toContain(STUDY_POSITION_PERMISSION);
});

it('shows the study position status on the admin profile', function (): void {
    ['context' => $context, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    studyPositionRegistryUser((int) $context['tenantId']);

    $this->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('studentStudyPosition.state', 'unconfirmed')
            ->where('studentStudyPosition.items.0.enrolmentId', (int) $enrolment->id)
            ->where('studentStudyPosition.items.0.canConfirm', true));
});

it('confirms a study position with a reason and audits who did it', function (): void {
    ['context' => $context, 'offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    $registry = studyPositionRegistryUser((int) $context['tenantId']);
    $target = $offering['phases'][1];

    $this->from(route('students.show', $student))
        ->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $target->id))
        ->assertRedirect(route('students.show', $student))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $confirmation = StudentStudyPositionConfirmation::query()->sole();

    expect($confirmation->source)->toBe(StudyPositionSourceEnum::ADMIN)
        ->and($confirmation->sync_status)->toBe(StudyPositionSyncStatusEnum::APPLIED)
        ->and((int) $confirmation->confirmed_by)->toBe((int) $registry->id)
        ->and(studyPositionPins($enrolment)['semester-2'])->toBe((int) $target->id);

    $activity = Activity::query()->where('event', 'study-position-confirmed')->latest('id')->firstOrFail();

    expect((int) $activity->causer_id)->toBe((int) $registry->id)
        ->and($activity->getExtraProperty('reason'))->toBe('Confirmed against the class register');
});

it('forbids users without the permission', function (): void {
    ['context' => $context, 'offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    $viewer = User::factory()->create(['tenant_id' => $context['tenantId']]);
    $viewer->givePermissionTo(['viewAny:students', 'view:students']);

    $this->actingAs($viewer)
        ->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $offering['phases'][1]->id))
        ->assertForbidden();

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('limits a department head to their own departments', function (): void {
    ['context' => $context, 'offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    $payload = studyPositionPayload($enrolment, (int) $offering['phases'][1]->id);

    $outsider = actingAsDepartmentScopedUser((int) $context['tenantId'], $context['otherInstitutionDepartment']);
    $outsider->givePermissionTo(['viewAny:students', STUDY_POSITION_PERMISSION]);

    $this->patch(route('students.study-position.update', $student), $payload)->assertForbidden();

    $this->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page->where('studentStudyPosition.items.0.canConfirm', false));

    $hod = actingAsDepartmentScopedUser((int) $context['tenantId'], $context['institutionDepartment']);
    $hod->givePermissionTo(['viewAny:students', STUDY_POSITION_PERMISSION]);

    $this->patch(route('students.study-position.update', $student), $payload)->assertSessionHasNoErrors();

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(1);
});

it('does not let staff confirm their own student record', function (): void {
    ['offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    $self = User::query()->findOrFail($student->user_id);
    $self->givePermissionTo(['viewAny:students', 'view:students', STUDY_POSITION_PERMISSION]);

    $this->actingAs($self)
        ->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $offering['phases'][1]->id))
        ->assertForbidden();
});

it('requires a meaningful reason', function (): void {
    ['context' => $context, 'offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    studyPositionRegistryUser((int) $context['tenantId']);

    $this->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $offering['phases'][1]->id, 'ok'))
        ->assertSessionHasErrors('reason');
});

it('rejects a phase from another programme', function (): void {
    ['context' => $context, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    studyPositionRegistryUser((int) $context['tenantId']);
    $other = makeMultiYearOffering($context);

    $this->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $other['phases'][1]->id))
        ->assertSessionHasErrors('positions.0.programme_semester_id')
        ->assertSessionHas('warning');

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('rejects an enrolment that belongs to another student', function (): void {
    ['context' => $context, 'offering' => $offering, 'student' => $student] = makeAdminStudyPositionStudent();
    $otherEnrolment = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $offering['phases'][0]]]);
    studyPositionRegistryUser((int) $context['tenantId']);

    $this->patch(route('students.study-position.update', $student), studyPositionPayload($otherEnrolment, (int) $offering['phases'][1]->id))
        ->assertSessionHasErrors('positions.0.programme_semester_id');

    expect(StudentStudyPositionConfirmation::query()->count())->toBe(0);
});

it('does not let a student use the admin endpoint', function (): void {
    ['offering' => $offering, 'student' => $student, 'enrolment' => $enrolment] = makeAdminStudyPositionStudent();
    $studentUser = User::query()->findOrFail($student->user_id);
    $studentUser->givePermissionTo([
        'viewOwnDashboard:students',
        'manageOwnStudentAcademicDetails:students',
        'manageOwnStudentPersonalDetails:students',
    ]);

    $this->actingAs($studentUser)
        ->patch(route('students.study-position.update', $student), studyPositionPayload($enrolment, (int) $offering['phases'][1]->id))
        ->assertForbidden();

    $this->actingAs($studentUser)->getJson(route('students.study-position.show', $student))->assertForbidden();
});
