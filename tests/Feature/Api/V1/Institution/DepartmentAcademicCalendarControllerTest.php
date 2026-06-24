<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
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
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentApplication;
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

test('department academic calendar returns totalnClass and totalFinalList counts', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) AcademicYearOption::query()->firstOrCreate(
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
        'academic_year_option_id' => $semesterOneId,
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

    $academicYearOption = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'dept-cal-api-count-option'],
        ['name' => 'Dept Cal API Count', 'description' => null],
    );
    $activeEnrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );
    $activeEnrolmentStatusId = (int) $activeEnrolmentStatus->id;
    $academicYearOptionId = (int) $academicYearOption->id;

    $studentEnrolmentIds = [];
    foreach ([1, 2, 3] as $_) {
        $studentEnrolmentIds[] = (int) StudentEnrolment::query()->create([
            'student_id' => $student->id,
            'student_application_id' => $studentApplication->id,
            'institution_department_id' => $institutionDepartment->id,
            'department_level_id' => $departmentLevel->id,
            'department_course_id' => $departmentCourse->id,
            'academic_year_option_id' => $academicYearOptionId,
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
    $response->assertJsonPath('meta.resolvedAcademicYearOptionId', $semesterOneId);
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'classConfigId' => $classConfig->id,
        'classesCount' => 2,
        'totalnClass' => 3,
        'totalFinalList' => 1,
        'academicYearOptionId' => $semesterOneId,
        'academicYearOption' => 'Semester 1',
        'courseSyllabusIds' => [$courseSyllabus->id],
        'courseSyllabusCodes' => [$courseSyllabus->code],
    ]);
});

test('department academic calendar auto seeds class config from final list count when config is missing', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) AcademicYearOption::query()->firstOrCreate(
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

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();

    $classConfig = ClassConfig::query()
        ->where('calendar_year', $calendar->calendar_year)
        ->where('institution_department_id', $institutionDepartment->id)
        ->where('department_course_id', $departmentCourse->id)
        ->where('department_level_id', $departmentLevel->id)
        ->where('mode_of_study_id', $modeOfStudy->id)
        ->where('academic_year_option_id', $semesterOneId)
        ->sole();

    expect($classConfig->students_per_class)->toBe(1)
        ->and($classConfig->academic_year_option_id)->toBe($semesterOneId);

    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'classConfigId' => $classConfig->id,
        'classesCount' => 0,
        'totalnClass' => 0,
        'totalFinalList' => 1,
        'studentsPerClass' => 1,
        'academicYearOptionId' => $semesterOneId,
        'academicYearOption' => 'Semester 1',
    ]);
});

test('department academic calendar does not overwrite existing class config students_per_class', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) AcademicYearOption::query()->firstOrCreate(
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
        'academic_year_option_id' => $semesterOneId,
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

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'classConfigId' => $existingConfig->id,
        'studentsPerClass' => 99,
        'totalFinalList' => 1,
        'academicYearOptionId' => $semesterOneId,
        'academicYearOption' => 'Semester 1',
    ]);

    expect(ClassConfig::query()->whereKey($existingConfig->id)->value('students_per_class'))->toBe(99);
    expect(ClassConfig::query()->where('institution_department_id', $institutionDepartment->id)->count())->toBe(1);
});

test('department academic calendar does not replace existing class config when students_per_class is zero', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) AcademicYearOption::query()->firstOrCreate(
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
        'academic_year_option_id' => $semesterOneId,
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

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'classConfigId' => $existingConfig->id,
        'studentsPerClass' => 0,
        'totalFinalList' => 1,
        'academicYearOptionId' => $semesterOneId,
        'academicYearOption' => 'Semester 1',
    ]);

    expect(ClassConfig::query()->where('institution_department_id', $institutionDepartment->id)->count())->toBe(1);
});

test('department academic calendar does not violate class config unique index when academic year option differs from resolved option', function () {
    $this->travelTo('2026-05-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $semesterOneId = (int) AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;
    $semesterTwoId = (int) AcademicYearOption::query()->firstOrCreate(
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
        'academic_year_option_id' => $semesterTwoId,
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

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/departments/{$institutionDepartment->id}/academic-calendars?academic_year={$calendar->calendar_year}&mode_of_study_id={$modeOfStudy->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'totalFinalList' => 1,
        'academicYearOptionId' => $semesterOneId,
        'academicYearOption' => 'Semester 1',
    ]);

    expect($existingConfig->fresh())
        ->students_per_class->toBe(25)
        ->academic_year_option_id->toBe($semesterTwoId);

    expect(ClassConfig::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->where('department_course_id', $departmentCourse->id)
        ->where('department_level_id', $departmentLevel->id)
        ->where('mode_of_study_id', $modeOfStudy->id)
        ->where('calendar_year', $calendar->calendar_year)
        ->where('academic_year_option_id', $semesterOneId)
        ->exists())->toBeTrue();
});

test('department academic calendar returns zero totalFinalList when class config and final list are missing', function () {
    $this->travelTo('2026-08-15');

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    AcademicYearOption::query()->firstOrCreate(
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
    $response->assertJsonPath('meta.resolvedAcademicYearOptionId', null);
    $response->assertJsonFragment([
        'departmentLevelId' => (string) $departmentLevel->id,
        'calendarType' => 'semester',
        'classConfigId' => null,
        'classesCount' => 0,
        'totalnClass' => 0,
        'totalFinalList' => 0,
        'academicYearOptionId' => null,
        'academicYearOption' => null,
    ]);
});
