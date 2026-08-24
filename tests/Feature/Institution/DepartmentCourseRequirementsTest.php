<?php

use App\Enums\Institution\LevelEnum;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

test('course requirements page serializes department levels for a course with attached level courses', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:department-metadata');

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'dcr_'.uniqid(),
        'description' => 'Course requirements test department',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => LevelEnum::NC->name()]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $this->actingAs($user)
        ->get(route('department-courses.requirements', $departmentCourse))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/departments/courses/CourseRequirements')
            ->has('levels', 1)
            ->where('levels.0.type', 'department-level')
            ->where('levels.0.id', $departmentLevel->id)
            ->where('levels.0.attributes.level', $level->name)
            ->where('allowedLevels', [$departmentLevel->id])
        );
});
