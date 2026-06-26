<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

function makeSyllabusModuleContext(?AcademicCalendarTypeEnum $calendarType = null): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo([
        'create:course-syllabuses',
        'update:course-syllabuses',
        'create:course-syllabus-modules',
        'update:course-syllabus-modules',
        'viewAny:course-syllabus-modules',
        'view:course-syllabus-modules',
        'view:course-syllabuses',
    ]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'mod_test_'.uniqid(),
        'description' => 'Syllabus module test department',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $resolvedCalendarType = $calendarType ?? AcademicCalendarTypeEnum::SEMESTER;
    $level = Level::factory()->create([
        'name' => 'Level 1',
        'calendar_type' => $resolvedCalendarType,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $courseSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Module Syllabus '.uniqid(),
        'code' => 'MS-'.uniqid(),
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $semesterOne = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );
    $termOne = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'term-1'],
        ['name' => 'Term 1', 'description' => null],
    );

    return compact(
        'tenant',
        'user',
        'institutionDepartment',
        'courseSyllabus',
        'semesterOne',
        'semesterTwo',
        'termOne',
    );
}
