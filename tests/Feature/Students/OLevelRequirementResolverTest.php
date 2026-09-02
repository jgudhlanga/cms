<?php

use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Tenants\Tenant;
use App\Services\Students\OLevelRequirementResolver;

function createRequirementResolverFixture(): array
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
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    return [$tenant, $departmentLevel, $departmentCourse];
}

function saveLevelRequirement(int $tenantId, int $departmentLevelId, bool $isOLevelRequired, int $mainCount): ApplicationLevelRequirement
{
    return ApplicationLevelRequirement::query()->create([
        'tenant_id' => $tenantId,
        'department_level_id' => $departmentLevelId,
        'is_o_level_required' => $isOLevelRequired,
        'required_subjects_count' => $mainCount,
        'main_subjects_count' => $mainCount,
        'main_subject_ids' => [],
        'other_subjects_count' => 0,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);
}

function saveCourseRequirement(
    int $tenantId,
    int $departmentLevelId,
    int $departmentCourseId,
    bool $isOLevelRequired,
    int $mainCount,
): ApplicationCourseRequirement {
    return ApplicationCourseRequirement::query()->create([
        'tenant_id' => $tenantId,
        'department_level_id' => $departmentLevelId,
        'department_course_id' => $departmentCourseId,
        'is_o_level_required' => $isOLevelRequired,
        'required_subjects_count' => $mainCount,
        'main_subjects_count' => $mainCount,
        'main_subject_ids' => [],
        'other_subjects_count' => 0,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);
}

test('o level requirement resolver prefers course when course requires o levels', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    saveLevelRequirement($tenant->id, $departmentLevel->id, true, 3);
    $courseRequirement = saveCourseRequirement($tenant->id, $departmentLevel->id, $departmentCourse->id, true, 5);

    $resolved = app(OLevelRequirementResolver::class)
        ->resolve($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeInstanceOf(ApplicationCourseRequirement::class)
        ->and($resolved->is($courseRequirement))->toBeTrue()
        ->and((int) $resolved->main_subjects_count)->toBe(5);
});

test('o level requirement resolver falls through to level when course does not require o levels', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    $levelRequirement = saveLevelRequirement($tenant->id, $departmentLevel->id, true, 4);
    saveCourseRequirement($tenant->id, $departmentLevel->id, $departmentCourse->id, false, 0);

    $resolved = app(OLevelRequirementResolver::class)
        ->resolve($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeInstanceOf(ApplicationLevelRequirement::class)
        ->and($resolved->is($levelRequirement))->toBeTrue()
        ->and((int) $resolved->main_subjects_count)->toBe(4);
});

test('o level requirement resolver returns null when neither requires o levels', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    saveLevelRequirement($tenant->id, $departmentLevel->id, false, 0);
    saveCourseRequirement($tenant->id, $departmentLevel->id, $departmentCourse->id, false, 0);

    $resolved = app(OLevelRequirementResolver::class)
        ->resolve($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeNull();
});

test('ranking resolver prefers a saved course requirement even when o level is off', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    saveLevelRequirement($tenant->id, $departmentLevel->id, true, 3);
    $courseRequirement = saveCourseRequirement($tenant->id, $departmentLevel->id, $departmentCourse->id, false, 2);

    $resolved = app(OLevelRequirementResolver::class)
        ->resolveRanking($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeInstanceOf(ApplicationCourseRequirement::class)
        ->and($resolved->is($courseRequirement))->toBeTrue()
        ->and((int) $resolved->main_subjects_count)->toBe(2);
});

test('ranking resolver falls back to the level requirement when the course has none', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    $levelRequirement = saveLevelRequirement($tenant->id, $departmentLevel->id, true, 3);

    $resolved = app(OLevelRequirementResolver::class)
        ->resolveRanking($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeInstanceOf(ApplicationLevelRequirement::class)
        ->and($resolved->is($levelRequirement))->toBeTrue();
});

test('ranking resolver returns null when neither requirement row exists', function () {
    [$tenant, $departmentLevel, $departmentCourse] = createRequirementResolverFixture();

    $resolved = app(OLevelRequirementResolver::class)
        ->resolveRanking($departmentLevel->id, $departmentCourse->id);

    expect($resolved)->toBeNull();
});
