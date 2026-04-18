<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
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
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Queries\Enrolments\ConfirmedStudentsQuery;

beforeEach(function () {
    $this->query = new ConfirmedStudentsQuery;
});

test('countsByCourseLevel excludes soft deleted final class lists', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-final-soft-del',
        'description' => 'ConfirmedStudentsQuery soft delete test',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Query Mode A']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Query intake',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $title = Title::query()->create(['name' => 'Mr Q']);
    $gender = Gender::query()->create(['title' => 'M']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S']);
    $idType = IdType::query()->create(['name' => 'ID']);
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
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
        'application_tracking_number' => 'APP-Q-SOFT',
    ]);

    $classList = ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $key = "{$departmentCourse->id}_{$departmentLevel->id}";

    expect($this->query->countsByCourseLevel((int) $institutionDepartment->id, $modeOfStudy->id, '2026'))->toHaveKey($key)
        ->and($this->query->countsByCourseLevel((int) $institutionDepartment->id, $modeOfStudy->id, '2026')[$key])->toBe(1);

    $classList->delete();

    expect($this->query->countsByCourseLevel((int) $institutionDepartment->id, $modeOfStudy->id, '2026'))->not->toHaveKey($key);
});

test('countsByCourseLevel is isolated by mode of study', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-mode-iso',
        'description' => 'ConfirmedStudentsQuery mode isolation',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeA = ModeOfStudy::query()->create(['name' => 'Query Mode X']);
    $modeB = ModeOfStudy::query()->create(['name' => 'Query Mode Y']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Query intake 2',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $title = Title::query()->create(['name' => 'Ms Q']);
    $gender = Gender::query()->create(['title' => 'F']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S2']);
    $idType = IdType::query()->create(['name' => 'ID2']);
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2002-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeA->id,
        'application_tracking_number' => 'APP-Q-MODE-A',
    ]);

    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $key = "{$departmentCourse->id}_{$departmentLevel->id}";

    expect($this->query->countsByCourseLevel((int) $institutionDepartment->id, $modeA->id, '2026'))->toHaveKey($key)
        ->and($this->query->countsByCourseLevel((int) $institutionDepartment->id, $modeB->id, '2026'))->not->toHaveKey($key);
});

test('listForClassAllocation returns empty without matching student enrolment', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-list-no-enr',
        'description' => 'listForClassAllocation enrolment gate',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Query Mode List']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Query intake 3',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->startOfMonth()->toDateString(),
        'closing_date' => now()->endOfMonth()->toDateString(),
    ]);

    $title = Title::query()->create(['name' => 'Mx Q']);
    $gender = Gender::query()->create(['title' => 'X']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S3']);
    $idType = IdType::query()->create(['name' => 'ID3']);
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2003-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-Q-LIST',
    ]);

    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $rows = $this->query->listForClassAllocation(
        (int) $institutionDepartment->id,
        $departmentLevel->id,
        $departmentCourse->id,
        $modeOfStudy->id,
        [$calendar->id],
    );

    expect($rows)->toHaveCount(0);
});

test('listForClassAllocation returns row when final list and enrolment exist', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-list-with-enr',
        'description' => 'listForClassAllocation happy path',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Query Mode List B']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Query intake 4',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->startOfMonth()->toDateString(),
        'closing_date' => now()->endOfMonth()->toDateString(),
    ]);

    $academicYearOption = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'q-list-option'],
        ['name' => 'Q List Option', 'description' => null],
    );
    $activeEnrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );

    $title = Title::query()->create(['name' => 'Prof Q']);
    $gender = Gender::query()->create(['title' => 'Z']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'S4']);
    $idType = IdType::query()->create(['name' => 'ID4']);
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2004-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-Q-LIST-OK',
    ]);

    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'student_enrolment_status_id' => $activeEnrolmentStatus->id,
    ]);

    $rows = $this->query->listForClassAllocation(
        (int) $institutionDepartment->id,
        $departmentLevel->id,
        $departmentCourse->id,
        $modeOfStudy->id,
        [$calendar->id],
    );

    expect($rows)->toHaveCount(1)
        ->and((int) $rows->first()->student_enrolment_id)->toBe((int) $enrolment->id);
});

test('listForClassAllocation returns empty when no academic calendar ids are provided', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-list-empty-ids',
        'description' => 'listForClassAllocation empty ids',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Query Mode List Empty Ids']);

    $rows = $this->query->listForClassAllocation(
        (int) $institutionDepartment->id,
        $departmentLevel->id,
        $departmentCourse->id,
        $modeOfStudy->id,
        [],
    );

    expect($rows)->toHaveCount(0);
});

test('listForClassAllocation matches student enrolment when any calendar id in the year list matches', function () {
    $tenant = Tenant::query()->firstOrFail();

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'q-list-multi-cal',
        'description' => 'listForClassAllocation multi calendar year',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Query Mode List Multi']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Query intake multi-cal',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $calendarOlder = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->subDays(60)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    $calendarNewer = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->subDays(5)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    $academicYearOption = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'q-list-option-multi'],
        ['name' => 'Q List Option Multi', 'description' => null],
    );
    $activeEnrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active Multi Cal'],
        ['description' => 'Test'],
    );

    $title = Title::query()->create(['name' => 'Dr Q']);
    $gender = Gender::query()->create(['title' => 'Multi']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'SM']);
    $idType = IdType::query()->create(['name' => 'ID-MULTI']);
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2005-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-Q-MULTI-CAL',
    ]);

    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendarOlder->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'student_enrolment_status_id' => $activeEnrolmentStatus->id,
    ]);

    $onlyCanonical = $this->query->listForClassAllocation(
        (int) $institutionDepartment->id,
        $departmentLevel->id,
        $departmentCourse->id,
        $modeOfStudy->id,
        [$calendarNewer->id],
    );

    expect($onlyCanonical)->toHaveCount(0);

    $allForYear = $this->query->listForClassAllocation(
        (int) $institutionDepartment->id,
        $departmentLevel->id,
        $departmentCourse->id,
        $modeOfStudy->id,
        [$calendarOlder->id, $calendarNewer->id],
    );

    expect($allForYear)->toHaveCount(1)
        ->and((int) $allForYear->first()->student_enrolment_id)->toBe((int) $enrolment->id);
});
