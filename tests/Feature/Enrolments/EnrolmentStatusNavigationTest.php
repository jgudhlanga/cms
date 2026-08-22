<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\EnrolmentHelper;
use App\Helpers\PermissionHelper;
use App\Models\Enrolments\ClassList;
use App\Models\Rbac\Permission;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;

function makeEnrolmentStatusNavigationUser(array $permissions, ?int $tenantId = null): User
{
    $user = User::factory()->create([
        'tenant_id' => $tenantId ?? TenantEnum::HARARE_POLY->id(),
    ]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function enrolmentClassListUrl(StudentApplication $application, string $type): string
{
    return route('enrolments.class-lists', [
        'institution_department' => $application->institution_department_id,
        'department_level' => $application->department_level_id,
        'intake_period_id' => $application->intake_period_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'department_course_id' => $application->department_course_id,
        'type' => $type,
    ]);
}

function enrolmentDepartmentApplicationsUrl(StudentApplication $application, string $type, array $extra = []): string
{
    return route('enrolments.department-applications', [
        'institution_department' => $application->institution_department_id,
        'intake_period_id' => $application->intake_period_id,
        'type' => $type,
        ...$extra,
    ]);
}

test('enrolments module includes confirm class lists permission', function () {
    expect(config('permissions.groups.enrolments'))->toContain('confirm:class-lists');
});

test('class list browse permission is mapped per status type', function () {
    expect(EnrolmentHelper::classListBrowsePermissionForType('provisional'))->toBe('verify:class-lists')
        ->and(EnrolmentHelper::classListBrowsePermissionForType('waiting'))->toBeNull()
        ->and(EnrolmentHelper::classListBrowsePermissionForType('failed'))->toBeNull()
        ->and(EnrolmentHelper::classListBrowsePermissionForType('verified'))->toBe('confirm:class-lists')
        ->and(EnrolmentHelper::classListBrowsePermissionForType('final'))->toBe('manage-final:class-lists')
        ->and(EnrolmentHelper::classListBrowsePermissionForType(null))->toBeNull()
        ->and(EnrolmentHelper::classListBrowseTypes())->toBe(['provisional', 'verified', 'final']);
});

test('application view permissions cannot open class list or verification pages', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-VIEW-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = makeEnrolmentStatusNavigationUser(
        ['view:student-applications', 'viewAny:student-applications'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'provisional'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(enrolmentClassListUrl($application, ClassListTypeEnum::PROVISIONAL->value))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('enrolments.verify', $application))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('enrolments.confirm', $application))
        ->assertForbidden();
});

test('registry officer permission pack cannot open class list navigation without class-list permissions', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-REG-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = makeEnrolmentStatusNavigationUser(
        PermissionHelper::registryOfficerPermissions(),
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'provisional'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(enrolmentClassListUrl($application, ClassListTypeEnum::PROVISIONAL->value))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('enrolments.verify', $application))
        ->assertForbidden();
});

test('verify class lists permission can open provisional lists and the verification page', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-VERIFY-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = makeEnrolmentStatusNavigationUser(
        ['verify:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'provisional'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrolments/DepartmentEnrolments'));

    $this->actingAs($user)
        ->get(enrolmentClassListUrl($application, ClassListTypeEnum::PROVISIONAL->value))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('enrolments.verify', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrolments/ApplicationVerification'));

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'verified'))
        ->assertForbidden();
});

test('confirm class lists permission can open verified lists and the confirmation page', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-CONFIRM-01');

    $user = makeEnrolmentStatusNavigationUser(
        ['confirm:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'verified'))
        ->assertOk();

    $this->actingAs($user)
        ->get(enrolmentClassListUrl($application, ClassListTypeEnum::VERIFIED->value))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('enrolments.confirm', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrolments/ApplicationConfirmation')
            ->has('otherApplications')
            ->has('nextTop')
        );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'final'))
        ->assertForbidden();
});

test('manage final class lists permission can open final lists but not verification', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-FINAL-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::FINAL->value,
    ]);

    $user = makeEnrolmentStatusNavigationUser(
        ['manage-final:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'final'))
        ->assertOk();

    $this->actingAs($user)
        ->get(enrolmentClassListUrl($application, ClassListTypeEnum::FINAL->value))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('enrolments.verify', $application))
        ->assertForbidden();
});

test('department applications require a browseable class list type', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-TYPE-01');
    $user = makeEnrolmentStatusNavigationUser(
        ['verify:class-lists', 'viewAny:student-applications'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(route('enrolments.department-applications', [
            'institution_department' => $application->institution_department_id,
            'intake_period_id' => $application->intake_period_id,
        ]))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'waiting'))
        ->assertForbidden();
});

test('department applications require an intake period', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-INTAKE-01');
    $user = makeEnrolmentStatusNavigationUser(
        ['verify:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->from('/enrolments')
        ->get(route('enrolments.department-applications', [
            'institution_department' => $application->institution_department_id,
            'type' => 'provisional',
        ]))
        ->assertRedirect('/enrolments')
        ->assertSessionHasErrors('intake_period_id');
});

test('department applications origin query does not change authorization', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-FROM-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = makeEnrolmentStatusNavigationUser(
        ['verify:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(enrolmentDepartmentApplicationsUrl($application, 'provisional', ['from' => 'dashboard']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrolments/DepartmentEnrolments'));
});

test('class lists without a browseable type are forbidden', function () {
    $application = createVerifiedStudentApplication('STATUS-NAV-CL-TYPE-01');
    $user = makeEnrolmentStatusNavigationUser(
        ['verify:class-lists'],
        (int) $application->tenant_id,
    );

    $this->actingAs($user)
        ->get(route('enrolments.class-lists', [
            'institution_department' => $application->institution_department_id,
            'department_level' => $application->department_level_id,
            'intake_period_id' => $application->intake_period_id,
            'mode_of_study_id' => $application->mode_of_study_id,
            'department_course_id' => $application->department_course_id,
        ]))
        ->assertForbidden();
});
