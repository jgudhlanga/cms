<?php

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

require_once __DIR__.'/../../../../Support/AcademicCalendarClassTestHelpers.php';

function attachDepartmentCalendarApiEnrolment(Student $student, StudentApplication $studentApplication, AcademicCalendar $calendar): void
{
    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'dept-cal-api-enrolment-option'],
        ['name' => 'Dept Cal API Enrolment', 'description' => null],
    );
    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $status->id,
    ]);
}

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

test('department academic calendar returns totalnClass and totalFinalList counts', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

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
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semesterOneId,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 2,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()
        ->where('department_course_id', $departmentCourse->id)
        ->where('department_level_id', $departmentLevel->id)
        ->firstOrFail();

    $courseSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Dept cal API syllabus '.$departmentCourse->id,
        'code' => 'DC-API-SYL-'.$departmentCourse->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $classConfig->update([
        'course_syllabus_ids' => [$courseSyllabus->id],
    ]);

    $calendarClassOne = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenant->id,
        'class_config_id' => $classConfig->id,
        'name' => 'Year Two 1',
        'description' => 'Test class one',
    ]);

    $calendarClassTwo = AcademicCalendarClass::query()->create([
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
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-COUNT',
    ]);
    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'dept-cal-api-count-option'],
        ['name' => 'Dept Cal API Count', 'description' => null],
    );
    $activeEnrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );
    $activeEnrolmentStatusId = (int) $activeEnrolmentStatus->id;
    $semesterId = (int) $semester->id;

    $studentEnrolmentIds = [];
    foreach ([1, 2, 3] as $_) {
        $studentEnrolmentIds[] = (int) StudentEnrolment::query()->create([
            'student_id' => $student->id,
            'student_application_id' => $studentApplication->id,
            'institution_department_id' => $institutionDepartment->id,
            'department_level_id' => $departmentLevel->id,
            'department_course_id' => $departmentCourse->id,
            'semester_id' => $semesterId,
            'academic_calendar_id' => $calendar->id,
            'mode_of_study_id' => $modeOfStudy->id,
            'student_enrolment_status_id' => $activeEnrolmentStatusId,
        ])->id;
    }

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $tenant->id,
        'student_enrolment_id' => $studentEnrolmentIds[0],
        'academic_calendar_class_id' => $calendarClassOne->id,
    ]);
    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $tenant->id,
        'student_enrolment_id' => $studentEnrolmentIds[1],
        'academic_calendar_class_id' => $calendarClassTwo->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonPath('meta.resolvedSemesterId', null);
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'totalFinalList' => 1,
    ]);
    $response->assertJsonFragment([
        'classConfigId' => $classConfig->id,
        'classesCount' => 2,
        'totalnClass' => 3,
        'semesterId' => $semesterOneId,
        'semester' => 'Semester 1',
        'courseSyllabusIds' => [$courseSyllabus->id],
        'courseSyllabusCodes' => [$courseSyllabus->code],
    ]);
});

test('department academic calendar does not auto seed class config on get', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-no-config',
        'description' => 'No config test department for academic calendar API',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Three']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Part Time']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 2 2026',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $title = Title::query()->create(['name' => 'Mrs Test']);
    $gender = Gender::query()->create(['title' => 'Female Test']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'Single Config Missing']);
    $idType = IdType::query()->create(['name' => 'Passport Test']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'calendar-no-config-student@example.com',
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
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-NO-CONFIG',
    ]);
    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);
    attachDepartmentCalendarApiEnrolment($student, $studentApplication, $calendar);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();

    expect(ClassConfig::query()
        ->where('calendar_year', $calendar->calendar_year)
        ->where('institution_department_id', $institutionDepartment->id)
        ->where('department_course_id', $departmentCourse->id)
        ->where('department_level_id', $departmentLevel->id)
        ->where('mode_of_study_id', $modeOfStudy->id)
        ->exists())->toBeFalse();

    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'totalFinalList' => 1,
        'configs' => [],
    ]);
});

test('department academic calendar does not overwrite existing class config students_per_class', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-no-overwrite',
        'description' => 'Existing config must not be overwritten on GET',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Five']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Weekend']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 4 2026',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $existingConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semesterOneId,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 99,
    ]);

    $title = Title::query()->create(['name' => 'Dr Test']);
    $gender = Gender::query()->create(['title' => 'Other Test']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'Married Test']);
    $idType = IdType::query()->create(['name' => 'Other ID Test']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'calendar-overwrite-student@example.com',
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
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-NO-OVERWRITE',
    ]);
    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);
    attachDepartmentCalendarApiEnrolment($student, $studentApplication, $calendar);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'totalFinalList' => 1,
    ]);
    $response->assertJsonFragment([
        'classConfigId' => $existingConfig->id,
        'studentsPerClass' => 99,
        'semesterId' => $semesterOneId,
        'semester' => 'Semester 1',
    ]);

    expect(ClassConfig::query()->whereKey($existingConfig->id)->value('students_per_class'))->toBe(99);
    expect(ClassConfig::query()->where('institution_department_id', $institutionDepartment->id)->count())->toBe(1);
});

test('department academic calendar does not replace existing class config when students_per_class is zero', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-zero-config',
        'description' => 'Existing zero students_per_class must remain',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Six']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Distance']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 5 2026',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $existingConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semesterOneId,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 0,
    ]);

    $title = Title::query()->create(['name' => 'Sir Test']);
    $gender = Gender::query()->create(['title' => 'NB']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S5']);
    $idType = IdType::query()->create(['name' => 'ID5']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'calendar-zero-config-student@example.com',
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
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-ZERO-CONFIG',
    ]);
    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);
    attachDepartmentCalendarApiEnrolment($student, $studentApplication, $calendar);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'totalFinalList' => 1,
    ]);
    $response->assertJsonFragment([
        'classConfigId' => $existingConfig->id,
        'studentsPerClass' => 0,
        'semesterId' => $semesterOneId,
        'semester' => 'Semester 1',
    ]);

    expect(ClassConfig::query()->where('institution_department_id', $institutionDepartment->id)->count())->toBe(1);
});

test('department academic calendar does not violate class config unique index when academic year option differs from resolved option', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;
    $semesterTwoId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    )->id;

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-option-mismatch',
        'description' => 'Existing config with different academic year option must not duplicate',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Seven']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Evening']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 6 2026',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $existingConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semesterTwoId,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 25,
    ]);

    $title = Title::query()->create(['name' => 'Prof Test']);
    $gender = Gender::query()->create(['title' => 'X']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S6']);
    $idType = IdType::query()->create(['name' => 'ID6']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'calendar-option-mismatch-student@example.com',
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
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CAL-OPTION-MISMATCH',
    ]);
    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);
    attachDepartmentCalendarApiEnrolment($student, $studentApplication, $calendar);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'totalFinalList' => 1,
    ]);
    $response->assertJsonFragment([
        'semesterId' => $semesterTwoId,
        'semester' => 'Semester 2',
        'classConfigId' => $existingConfig->id,
    ]);

    expect($existingConfig->fresh())
        ->students_per_class->toBe(25)
        ->semester_id->toBe($semesterTwoId);

    expect(ClassConfig::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->where('department_course_id', $departmentCourse->id)
        ->where('department_level_id', $departmentLevel->id)
        ->where('mode_of_study_id', $modeOfStudy->id)
        ->where('calendar_year', $calendar->calendar_year)
        ->where('semester_id', $semesterOneId)
        ->exists())->toBeFalse();
});

test('department academic calendar returns zero totalFinalList when class config and final list are missing', function () {
    $this->travelTo('2026-08-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-no-config-zero',
        'description' => 'No config and no final list test department for academic calendar API',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Year Four']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Block Release']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 3 2026',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonPath('meta.resolvedSemesterId', null);
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'totalFinalList' => 0,
        'configs' => [],
    ]);
});

test('department academic calendar groups multiple year configs onto one level row', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-group-configs',
        'description' => 'Two semester configs share one level row',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create([
        'name' => 'NC',
        'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Group']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $configOne = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterOne->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 20,
    ]);
    $configTwo = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterTwo->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 25,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year=2026&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $levels = $response->json('data.0.levels');
    expect($levels)->toHaveCount(1)
        ->and($levels[0]['departmentLevelId'])->toBe((string) $departmentLevel->id)
        ->and($levels[0]['configs'])->toHaveCount(2)
        ->and($levels[0]['remainingPeriods'])->toBe([])
        ->and(collect($levels[0]['configs'])->pluck('classConfigId')->all())->toEqualCanonicalizing([
            $configOne->id,
            $configTwo->id,
        ]);
});

test('department academic calendar remaining periods flag the current period for the selected year', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-remaining',
        'description' => 'Remaining periods for 2026',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create([
        'name' => 'ND',
        'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Remaining']);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year=2026&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $levelRow = $response->json('data.0.levels.0');
    expect($levelRow['configs'])->toBe([])
        ->and($levelRow['currentSemesterId'])->toBe($semesterOne->id)
        ->and($levelRow['remainingPeriods'])->toEqual([
            ['id' => $semesterOne->id, 'name' => 'Semester 1', 'isCurrent' => true],
            ['id' => $semesterTwo->id, 'name' => 'Semester 2', 'isCurrent' => false],
        ]);
});

test('department academic calendar hides other-year configs from the selected year', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-year-scope',
        'description' => '2025 configs stay out of 2026',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'HND']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Year Scope']);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $config2025 = ClassConfig::query()->create([
        'calendar_year' => '2025',
        'semester_id' => $semesterOneId,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 40,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year=2026&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $levelRow = $response->json('data.0.levels.0');
    expect($levelRow['configs'])->toBe([])
        ->and(collect($levelRow['configs'])->pluck('classConfigId')->all())->not->toContain($config2025->id);
});

test('department academic calendar semester filter hides other pills but keeps remaining math', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-filter-pills',
        'description' => 'Filter hides other semester pills',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create([
        'name' => 'NC Filter',
        'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Filter']);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    $configOne = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterOne->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 18,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year=2026&mode_of_study_id={$modeOfStudy->id}&semester_id={$semesterTwo->id}");

    $response->assertOk();
    $levelRow = $response->json('data.0.levels.0');
    expect($levelRow['configs'])->toBe([])
        ->and(collect($levelRow['configs'])->pluck('classConfigId')->all())->not->toContain($configOne->id)
        ->and($levelRow['remainingPeriods'])->toEqual([
            ['id' => $semesterTwo->id, 'name' => 'Semester 2', 'isCurrent' => false],
        ]);
});

test('department academic calendar returns mode totals without course rows when mode is omitted', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    createFinalStudentApplication($context, 'mode-totals-one@example.com');
    createFinalStudentApplication($context, 'mode-totals-two@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year);

    $response->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonStructure([
            'meta' => [
                'modeTotals' => [
                    '*' => ['modeOfStudyId', 'count'],
                ],
            ],
        ]);

    expect(collect($response->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $context['modeOfStudy']->id)['count'] ?? 0)
        ->toBe(2);
});

test('department academic calendar includes mode totals with course rows when mode is present', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    createFinalStudentApplication($context, 'mode-rows-one@example.com');
    createFinalStudentApplication($context, 'mode-rows-two@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk()
        ->assertJsonStructure([
            'meta' => [
                'modeTotals' => [
                    '*' => ['modeOfStudyId', 'count'],
                ],
            ],
        ]);

    expect($response->json('data'))->not->toBeEmpty()
        ->and(collect($response->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $context['modeOfStudy']->id)['count'] ?? 0)
        ->toBe(2);
});

test('department academic calendar includes ojet course rows when the offering has no industrial attachment', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    $context['modeOfStudy']->update(['name' => ModeOfStudyEnum::OJET->value]);
    DepartmentLevelCourse::query()
        ->where('department_course_id', $context['departmentCourse']->id)
        ->where('department_level_id', $context['departmentLevel']->id)
        ->update([
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ]);
    createFinalStudentApplication($context, 'ojet-no-attachment@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk();

    expect($response->json('data'))->not->toBeEmpty()
        ->and($response->json('data.0.levels.0.totalFinalList'))->toBe(1)
        ->and(collect($response->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $context['modeOfStudy']->id)['count'] ?? 0)
        ->toBe(1);
});

test('department academic calendar omits levels that do not offer the selected ojet mode', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    $context['modeOfStudy']->update(['name' => ModeOfStudyEnum::OJET->value]);

    $otherLevel = Level::factory()->create(['name' => 'AL3', 'calendar_type' => 'semester']);
    $otherDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $otherLevel->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $otherDepartmentLevel->id,
    ]);

    seedApplicationOffering(
        $context['institutionDepartment'],
        $context['departmentLevel'],
        $context['departmentCourse'],
        [(int) $context['modeOfStudy']->id],
    );
    createFinalStudentApplication($context, 'ojet-offered-nd@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk();
    $levelIds = collect($response->json('data.0.levels'))->pluck('departmentLevelId')->map(fn ($id) => (string) $id)->all();

    expect($levelIds)->toContain((string) $context['departmentLevel']->id)
        ->and($levelIds)->not->toContain((string) $otherDepartmentLevel->id)
        ->and($response->json('data.0.levels.0.totalFinalList'))->toBe(1);
});

test('department academic calendar omits empty ojet levels when no offering catalogue exists', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    $context['modeOfStudy']->update(['name' => ModeOfStudyEnum::OJET->value]);

    $otherLevel = Level::factory()->create(['name' => 'HND', 'calendar_type' => 'semester']);
    $otherDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $otherLevel->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $otherDepartmentLevel->id,
    ]);
    createFinalStudentApplication($context, 'ojet-occupied-nd@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk();
    $levelIds = collect($response->json('data.0.levels'))->pluck('departmentLevelId')->map(fn ($id) => (string) $id)->all();

    expect($levelIds)->toContain((string) $context['departmentLevel']->id)
        ->and($levelIds)->not->toContain((string) $otherDepartmentLevel->id);
});

test('department academic calendar includes empty attached levels for non-ojet modes', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();

    $otherLevel = Level::factory()->create(['name' => 'AL4', 'calendar_type' => 'semester']);
    $otherDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $otherLevel->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $otherDepartmentLevel->id,
    ]);

    seedApplicationOffering(
        $context['institutionDepartment'],
        $context['departmentLevel'],
        $context['departmentCourse'],
        [(int) $context['modeOfStudy']->id],
    );
    createFinalStudentApplication($context, 'full-time-occupied@example.com');

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk();
    $levelIds = collect($response->json('data.0.levels'))->pluck('departmentLevelId')->map(fn ($id) => (string) $id)->all();

    expect($levelIds)->toContain((string) $context['departmentLevel']->id)
        ->and($levelIds)->toContain((string) $otherDepartmentLevel->id);
});

test('department academic calendar includes offered ojet levels with no confirmed students', function () {
    $this->travelTo('2026-05-15');

    $context = buildDepartmentClassContext();
    $context['modeOfStudy']->update(['name' => ModeOfStudyEnum::OJET->value]);

    $emptyOfferedLevel = Level::factory()->create(['name' => 'NC', 'calendar_type' => 'semester']);
    $emptyOfferedDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $emptyOfferedLevel->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $emptyOfferedDepartmentLevel->id,
    ]);

    $unofferedLevel = Level::factory()->create(['name' => 'AL4', 'calendar_type' => 'semester']);
    $unofferedDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $unofferedLevel->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $unofferedDepartmentLevel->id,
    ]);

    seedApplicationOffering(
        $context['institutionDepartment'],
        $context['departmentLevel'],
        $context['departmentCourse'],
        [(int) $context['modeOfStudy']->id],
    );
    seedApplicationOffering(
        $context['institutionDepartment'],
        $emptyOfferedDepartmentLevel,
        $context['departmentCourse'],
        [(int) $context['modeOfStudy']->id],
    );

    Sanctum::actingAs($context['user']);

    $response = $this->getJson('/api/v1/departments/'.$context['institutionDepartment']->id.'/academic-calendars?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id);

    $response->assertOk();
    $levelIds = collect($response->json('data.0.levels'))->pluck('departmentLevelId')->map(fn ($id) => (string) $id)->all();

    expect($levelIds)->toContain((string) $context['departmentLevel']->id)
        ->and($levelIds)->toContain((string) $emptyOfferedDepartmentLevel->id)
        ->and($levelIds)->not->toContain((string) $unofferedDepartmentLevel->id);
});

test('department academic calendar remaining periods use programme semester ids when the offering has them', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'cal-api-prog-remaining',
        'description' => 'Programme semester remaining periods',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create([
        'name' => 'NC Programme',
        'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $dlc = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'duration_years' => 1,
        'taught_semester_count' => 2,
        'includes_industrial_attachment' => false,
        'attachment_semester_count' => 0,
    ]);
    $programmeSemesters = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh() ?? $dlc);
    $yearOneSemOne = $programmeSemesters->firstWhere('name', 'Year 1 Sem 1');
    $yearOneSemTwo = $programmeSemesters->firstWhere('name', 'Year 1 Sem 2');

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Programme Remaining']);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-06-30',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year=2026&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $levelRow = $response->json('data.0.levels.0');
    expect($levelRow['remainingPeriods'])->toEqual([
        [
            'id' => $yearOneSemOne->id,
            'programmeSemesterId' => $yearOneSemOne->id,
            'name' => 'Year 1 Sem 1',
            'isCurrent' => false,
        ],
        [
            'id' => $yearOneSemTwo->id,
            'programmeSemesterId' => $yearOneSemTwo->id,
            'name' => 'Year 1 Sem 2',
            'isCurrent' => false,
        ],
    ]);
});
