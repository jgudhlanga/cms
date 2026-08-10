<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
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
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     classConfig: ClassConfig,
 *     syllabi: list<CourseSyllabus>,
 *     secondConfig: ClassConfig
 * }
 */
function createClassConfigSyllabusRelationContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $suffix = uniqid();

    $department = Department::factory()->create(['name' => 'Syllabus Rel '.$suffix]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'SR-'.$suffix,
        'description' => 'ClassConfig syllabus relation test',
    ]);

    $course = Course::factory()->create(['name' => 'Course '.$suffix]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Level '.$suffix]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'tenant_id' => $tenant->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time SR '.$suffix]);
    $semester = Semester::query()->create([
        'name' => 'Semester SR '.$suffix,
        'description' => null,
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $syllabusA = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Syllabus A '.$suffix,
        'code' => 'SYL-A-'.$suffix,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $syllabusB = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Syllabus B '.$suffix,
        'code' => 'SYL-B-'.$suffix,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $classConfig = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semester->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 20,
        'course_syllabus_ids' => [$syllabusB->id, $syllabusA->id],
    ]);

    $semesterTwo = Semester::query()->create([
        'name' => 'Semester SR 2 '.$suffix,
        'description' => null,
    ]);

    $secondConfig = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterTwo->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 15,
        'course_syllabus_ids' => [$syllabusA->id],
    ]);

    return [
        'classConfig' => $classConfig,
        'syllabi' => [$syllabusA, $syllabusB],
        'secondConfig' => $secondConfig,
    ];
}

test('class config syllabus relation lazy loads course syllabi in stored order', function () {
    $context = createClassConfigSyllabusRelationContext();
    /** @var ClassConfig $classConfig */
    $classConfig = $context['classConfig']->fresh();
    [$syllabusA, $syllabusB] = $context['syllabi'];

    $syllabi = $classConfig->syllabus;

    expect($syllabi)->toHaveCount(2)
        ->and($syllabi->pluck('id')->all())->toBe([$syllabusB->id, $syllabusA->id]);
});

test('class config syllabus relation can be eager loaded without RelationNotFoundException', function () {
    $context = createClassConfigSyllabusRelationContext();
    [$syllabusA, $syllabusB] = $context['syllabi'];

    $classConfig = ClassConfig::query()
        ->with('syllabus')
        ->findOrFail($context['classConfig']->id);

    expect($classConfig->relationLoaded('syllabus'))->toBeTrue()
        ->and($classConfig->syllabus)->toHaveCount(2)
        ->and($classConfig->syllabus->pluck('id')->all())->toBe([$syllabusB->id, $syllabusA->id]);
});

test('eager loading syllabus for multiple class configs does not n+1', function () {
    $context = createClassConfigSyllabusRelationContext();
    $ids = [$context['classConfig']->id, $context['secondConfig']->id];

    $courseSyllabusSelects = 0;
    DB::listen(function (QueryExecuted $query) use (&$courseSyllabusSelects): void {
        if (str_contains($query->sql, 'from `course_syllabuses`')
            || str_contains($query->sql, 'from "course_syllabuses"')) {
            $courseSyllabusSelects++;
        }
    });

    $configs = ClassConfig::query()
        ->with('syllabus')
        ->whereIn('id', $ids)
        ->get();

    expect($configs)->toHaveCount(2)
        ->and($courseSyllabusSelects)->toBe(1)
        ->and($configs->firstWhere('id', $context['classConfig']->id)?->syllabus)->toHaveCount(2)
        ->and($configs->firstWhere('id', $context['secondConfig']->id)?->syllabus)->toHaveCount(1);
});

test('empty course_syllabus_ids yields empty syllabus relation', function () {
    $context = createClassConfigSyllabusRelationContext();
    $context['classConfig']->update(['course_syllabus_ids' => []]);

    $classConfig = ClassConfig::query()
        ->with('syllabus')
        ->findOrFail($context['classConfig']->id);

    expect($classConfig->syllabus)->toHaveCount(0);
});
