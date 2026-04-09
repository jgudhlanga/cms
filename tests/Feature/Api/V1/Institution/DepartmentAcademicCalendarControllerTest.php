<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\AcademicCalendars\AcademicCalendarStudentProgram;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('department academic calendar resolves course levels when department level is soft deleted', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-test',
        'description' => 'Test department for academic calendar API',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year One']);

    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $departmentLevel->delete();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars");

    $response->assertOk();
    $response->assertJsonFragment([
        'levelName' => 'Year One',
    ]);
});

test('department academic calendar returns totalFinalClass from academic calendar student programs', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-count',
        'description' => 'Count test department for academic calendar API',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Two']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendarOption = AcademicCalendarOption::query()->create([
        'name' => 'Semester 1',
        'description' => 'Semester 1 option',
    ]);
    $calendar = AcademicCalendar::query()->create([
        'academic_calendar_option_id' => $calendarOption->id,
        'calendar_year' => '2026',
        'opening_date' => now()->startOfMonth()->toDateString(),
        'closing_date' => now()->endOfMonth()->toDateString(),
        'intake_period_ids' => [$intakePeriod->id],
    ]);

    $classConfig = ClassConfig::query()->create([
        'academic_calendar_id' => $calendar->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 2,
    ]);

    $academicCalendarClassOne = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenant->id,
        'class_config_id' => $classConfig->id,
        'name' => 'Year Two 1',
        'description' => 'Test class one',
    ]);

    $academicCalendarClassTwo = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenant->id,
        'class_config_id' => $classConfig->id,
        'name' => 'Year Two 2',
        'description' => 'Test class two',
    ]);

    $title = Title::query()->create(['name' => 'Mr Test']);
    $gender = Gender::query()->create(['title' => 'Male Test']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'Single Test']);
    $idType = IdType::query()->create(['name' => 'National ID Test']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'calendar-count-student@example.com',
    ]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2001-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-COUNT',
    ]);

    AcademicCalendarStudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'academic_calendar_class_id' => $academicCalendarClassOne->id,
    ]);
    AcademicCalendarStudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'academic_calendar_class_id' => $academicCalendarClassOne->id,
    ]);
    AcademicCalendarStudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'academic_calendar_class_id' => $academicCalendarClassTwo->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_calendar={$calendar->id}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'classConfigId' => $classConfig->id,
        'totalFinalClass' => 3,
    ]);
});
