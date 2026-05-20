<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

test('per class size store rejects course syllabus from another department level course', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-syl',
        'description' => 'Per class size syllabus validation',
    ]);

    $courseA = Course::factory()->create();
    $departmentCourseA = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $courseA->id,
    ]);

    $courseB = Course::factory()->create();
    $departmentCourseB = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $courseB->id,
    ]);

    $levelA = Level::factory()->create();
    $departmentLevelA = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $levelA->id,
    ]);

    $levelB = Level::factory()->create();
    $departmentLevelB = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $levelB->id,
    ]);

    $dlcA = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourseA->id,
        'department_level_id' => $departmentLevelA->id,
    ]);

    $dlcB = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourseB->id,
        'department_level_id' => $departmentLevelB->id,
    ]);

    $syllabusOnA = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $dlcA->id,
        'title' => 'Syllabus on course A '.$departmentCourseA->id,
        'code' => 'SYL-A-'.$departmentCourseA->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $syllabusOnB = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $dlcB->id,
        'title' => 'Syllabus on course B '.$departmentCourseB->id,
        'code' => 'SYL-B-'.$departmentCourseB->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time PCS']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $academicYearOption = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-pcs-syl-test'],
        ['name' => 'Semester PCS Syl', 'description' => null],
    );

    ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'academic_year_option_id' => $academicYearOption->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourseA->id,
        'department_level_id' => $departmentLevelA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 3,
    ]);

    $this->actingAs($user);

    $url = route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post($url, [
        'students_per_class' => 4,
        'department_level_id' => $departmentLevelA->id,
        'department_course_id' => $departmentCourseA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'academic_year_option_id' => $academicYearOption->id,
        'course_syllabus_ids' => [$syllabusOnB->id],
    ])->assertSessionHasErrors(['course_syllabus_ids.0']);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post($url, [
        'students_per_class' => 4,
        'department_level_id' => $departmentLevelA->id,
        'department_course_id' => $departmentCourseA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'academic_year_option_id' => $academicYearOption->id,
        'course_syllabus_ids' => [$syllabusOnA->id],
    ])->assertSessionHasNoErrors();

    $updated = ClassConfig::query()->where('department_course_id', $departmentCourseA->id)->first();
    expect($updated)->not->toBeNull()
        ->and((int) $updated->students_per_class)->toBe(4)
        ->and($updated->course_syllabus_ids)->toBe([$syllabusOnA->id]);
});
