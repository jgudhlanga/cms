<?php

declare(strict_types=1);

use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Rbac\Permission;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\OLevelRequirementResolver;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Permission::findOrCreate('manage:online-application-catalogue', 'web');
});

function requirementFixture(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
    ]);
    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $course = Course::factory()->create(['has_enrolment_requirements' => true]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    return [$tenant, $institutionDepartment, $departmentLevel, $departmentCourse];
}

test('backfill command reports legacy tables removed after phase b', function () {
    if (Schema::hasTable('department_level_requirements')) {
        $this->markTestSkipped('Legacy tables still present.');
    }

    $this->artisan('enrolments:backfill-requirements', ['--dry-run' => true])
        ->assertFailed();
});

test('enrolment setup requirement pages require manage permission', function () {
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    [, $institutionDepartment] = requirementFixture();

    $this->actingAs($user)
        ->get(route('application-requirements.department', $institutionDepartment->id))
        ->assertForbidden();
});

test('department requirement routes are removed', function () {
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('update:department-metadata');

    [, , $departmentLevel] = requirementFixture();

    $this->actingAs($user)
        ->get("/institution/departments/{$departmentLevel->id}/requirements")
        ->assertNotFound();
});

test('o level requirement resolver reads application tables', function () {
    [$tenant, , $departmentLevel, $departmentCourse] = requirementFixture();

    ApplicationLevelRequirement::query()->create([
        'tenant_id' => $tenant->id,
        'department_level_id' => $departmentLevel->id,
        'is_o_level_required' => true,
        'required_subjects_count' => 5,
        'main_subjects_count' => 2,
        'main_subject_ids' => [],
        'other_subjects_count' => 3,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);

    ApplicationCourseRequirement::query()->create([
        'tenant_id' => $tenant->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'is_o_level_required' => true,
        'required_subjects_count' => 4,
        'main_subjects_count' => 2,
        'main_subject_ids' => [],
        'other_subjects_count' => 2,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);

    $resolved = app(OLevelRequirementResolver::class)->resolve($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeInstanceOf(ApplicationCourseRequirement::class)
        ->and($resolved->main_subjects_count)->toBe(2);
});

function makeRequirementsManager(): User
{
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('manage:online-application-catalogue');

    return $user;
}

test('enrolment setup can save level requirements', function () {
    $user = makeRequirementsManager();
    [$tenant, $institutionDepartment, $departmentLevel] = requirementFixture();

    $this->actingAs($user)
        ->from(route('application-requirements.level', [
            'institution_department' => $institutionDepartment->id,
            'department_level' => $departmentLevel->id,
        ]))
        ->post(route('application-requirements.level.store', [
            'institution_department' => $institutionDepartment->id,
            'department_level' => $departmentLevel->id,
        ]), [
            'is_o_level_required' => true,
            'required_subjects_count' => 5,
            'main_subjects_count' => 2,
            'main_subject_ids' => [],
            'other_subjects_count' => 3,
            'only_read_write_required' => false,
            'required_level_id' => null,
        ])
        ->assertRedirect(route('application-requirements.department', $institutionDepartment->id));

    expect(ApplicationLevelRequirement::query()
        ->where('tenant_id', $tenant->id)
        ->where('department_level_id', $departmentLevel->id)
        ->where('is_o_level_required', true)
        ->exists())->toBeTrue();
});

test('requirements index only lists courses with has enrolment requirements', function () {
    $user = makeRequirementsManager();
    [$tenant, $institutionDepartment, $departmentLevel, $overrideCourse] = requirementFixture();

    $plainCourse = Course::factory()->create(['has_enrolment_requirements' => false]);
    $plainDepartmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $plainCourse->id,
    ]);
    \App\Models\Institution\DepartmentLevelCourse::query()->create([
        'tenant_id' => $tenant->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $plainDepartmentCourse->id,
    ]);
    \App\Models\Institution\DepartmentLevelCourse::query()->create([
        'tenant_id' => $tenant->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $overrideCourse->id,
    ]);

    $this->actingAs($user)
        ->get(route('application-requirements.department', $institutionDepartment->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/enrolments/requirements/Index')
            ->has('courses', 1)
            ->where('courses.0.id', $overrideCourse->id)
        );
});

test('course requirements page is unavailable when course has no enrolment requirements flag', function () {
    $user = makeRequirementsManager();
    [$tenant, $institutionDepartment, $departmentLevel] = requirementFixture();

    $plainCourse = Course::factory()->create(['has_enrolment_requirements' => false]);
    $plainDepartmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $plainCourse->id,
    ]);

    $this->actingAs($user)
        ->get(route('application-requirements.course', [
            'institution_department' => $institutionDepartment->id,
            'department_course' => $plainDepartmentCourse->id,
            'department_level_id' => $departmentLevel->id,
        ]))
        ->assertNotFound();
});
