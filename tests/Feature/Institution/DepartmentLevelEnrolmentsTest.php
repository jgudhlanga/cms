<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Models\Institution\IntakePeriod;
use App\Models\Rbac\Permission;
use App\Models\Users\User;

function makeDepartmentLevelEnrolmentsUser(array $permissions = ['view:department-metadata']): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function makeInactiveIntakePeriod(): IntakePeriod
{
    return IntakePeriod::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'name' => 'Inactive Intake '.uniqid(),
        'start_date' => now()->subYears(3)->toDateString(),
        'end_date' => now()->subYears(2)->toDateString(),
        'calendar_year' => '2023/2024',
        'is_active' => false,
        'status' => IntakePeriodStatusEnum::Closed,
        'is_continuous' => false,
    ]);
}

it('loads course level enrolments for an intake period missing from the admin dropdown', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser();
    $inactive = makeInactiveIntakePeriod();
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('department-levels.enrolments', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $inactive->id,
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/CourseLevelEnrolments')
            ->where('intakePeriod.id', $inactive->id)
        );
});

it('loads course level enrolments when intake and mode filters are omitted', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser();
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('department-levels.enrolments', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/CourseLevelEnrolments')
            ->has('intakePeriod.id')
            ->has('modeOfStudy.id')
        );
});

it('loads class lists for an intake period missing from the admin dropdown', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser(['view:department-metadata', 'verify:class-lists']);
    $inactive = makeInactiveIntakePeriod();
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('enrolments.class-lists', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $inactive->id,
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
            'type' => 'provisional',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrolments/ClassList')
            ->where('intakePeriod.id', $inactive->id)
        );
});

it('loads course level enrolments without others gender group', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser();
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('department-levels.enrolments', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/CourseLevelEnrolments')
            ->has('enrolments.groups.disabled')
            ->has('enrolments.groups.females')
            ->has('enrolments.groups.males')
            ->missing('enrolments.groups.others')
            ->has('intakePeriod.id')
            ->has('modeOfStudy.id')
        );
});

it('updates intake limit for authorized users', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser(['view:department-metadata', 'department-setup:class-sizes']);

    $this->actingAs($user)
        ->put(route('class-sizes.update', $seeded['departmentId']), [
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'class_size' => 35,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('department_intake_class_sizes', [
        'institution_department_id' => $seeded['departmentId'],
        'department_course_id' => $seeded['courseId'],
        'department_level_id' => $seeded['departmentLevelId'],
        'intake_period_id' => $seeded['intakeId'],
        'mode_of_study_id' => $seeded['modeId'],
        'class_size' => 35,
    ]);
});

it('forbids intake limit updates without department-setup:class-sizes', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser(['view:department-metadata']);

    $this->actingAs($user)
        ->put(route('class-sizes.update', $seeded['departmentId']), [
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'class_size' => 40,
        ])
        ->assertForbidden();
});

function seedEnrolmentRequirements(array $seeded, bool $withCourseOverride): ?ApplicationCourseRequirement
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    ApplicationLevelRequirement::query()->create([
        'tenant_id' => $tenantId,
        'department_level_id' => $seeded['departmentLevelId'],
        'is_o_level_required' => true,
        'required_subjects_count' => 5,
        'main_subjects_count' => 3,
        'main_subject_ids' => [1, 2, 3],
        'other_subjects_count' => 2,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);

    if (! $withCourseOverride) {
        return null;
    }

    return ApplicationCourseRequirement::query()->create([
        'tenant_id' => $tenantId,
        'department_level_id' => $seeded['departmentLevelId'],
        'department_course_id' => $seeded['courseId'],
        'is_o_level_required' => true,
        'required_subjects_count' => 5,
        'main_subjects_count' => 2,
        'main_subject_ids' => [1, 2],
        'other_subjects_count' => 3,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);
}

it('includes the course requirement override on the enrolments page', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser();
    $courseRequirement = seedEnrolmentRequirements($seeded, true);
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('department-levels.enrolments', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/CourseLevelEnrolments')
            ->where('courseRequirement.id', $courseRequirement->id)
            ->where('courseRequirement.attributes.mainSubjectsCount', 2)
            ->where('courseRequirement.attributes.otherSubjectsCount', 3)
        );
});

it('omits courseRequirement on the enrolments page when the course has no override', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser();
    seedEnrolmentRequirements($seeded, false);
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('department-levels.enrolments', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/CourseLevelEnrolments')
            ->where('courseRequirement', null)
        );
});

it('includes the course requirement override on the class list page', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = makeDepartmentLevelEnrolmentsUser(['view:department-metadata', 'verify:class-lists']);
    $courseRequirement = seedEnrolmentRequirements($seeded, true);
    cache()->forget('all_intake_periods');
    cache()->forget('all_modes_of_study');

    $this->actingAs($user)
        ->get(route('enrolments.class-lists', [
            'institution_department' => $seeded['departmentId'],
            'department_level' => $seeded['departmentLevelId'],
            'intake_period_id' => $seeded['intakeId'],
            'mode_of_study_id' => $seeded['modeId'],
            'department_course_id' => $seeded['courseId'],
            'type' => 'provisional',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrolments/ClassList')
            ->where('courseRequirement.id', $courseRequirement->id)
            ->where('courseRequirement.attributes.mainSubjectsCount', 2)
        );
});
