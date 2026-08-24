<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
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
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\SyncStudentSemestersForEnrolmentService;

function buildDepartmentClassContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ict',
        'description' => 'Department for classes tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create(['name' => 'Level 1', 'calendar_type' => 'semester']);
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
        'opening_date' => now()->subDays(30)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);
    $semesterOneId = (int) Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;
    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterOneId,
        'students_per_class' => 2,
    ]);

    return compact(
        'tenant',
        'user',
        'institutionDepartment',
        'departmentCourse',
        'departmentLevel',
        'modeOfStudy',
        'intakePeriod',
        'calendar',
        'classConfig'
    );
}

function ensureStudentSemesterPhaseForEnrolment(StudentEnrolment $enrolment): void
{
    $enrolment->loadMissing('departmentLevel.level');

    if ($enrolment->departmentLevel?->level !== null && $enrolment->departmentLevel->level->calendar_type === null) {
        $enrolment->departmentLevel->level->update(['calendar_type' => 'semester']);
    }

    if ($enrolment->studentSemesters()->exists()) {
        return;
    }

    if ($enrolment->semester_id === null || $enrolment->student_enrolment_status_id === null) {
        return;
    }

    $synced = app(SyncStudentSemestersForEnrolmentService::class)->sync($enrolment, null, [
        'sourceSemesterId' => $enrolment->semester_id,
        'sourceStatusId' => $enrolment->student_enrolment_status_id,
        'snapshotEnrolment' => false,
    ]);

    if ($synced !== []) {
        return;
    }

    StudentSemester::query()->firstOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $enrolment->semester_id,
        ],
        [
            'student_enrolment_status_id' => $enrolment->student_enrolment_status_id,
        ],
    );
}

function createFinalStudentApplication(array $context, string $email, string $genderTitle = 'Male'): StudentApplication
{
    $title = Title::query()->create(['name' => 'Mr '.str($email)->before('@')]);
    $gender = Gender::query()->create(['title' => $genderTitle.' '.str($email)->before('@')]);
    $marital = MaritalStatus::query()->create(['title' => 'Single '.str($email)->before('@')]);
    $idType = IdType::query()->create(['name' => 'National ID '.str($email)->before('@')]);
    $studentUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'email' => $email,
        'first_name' => 'Student',
        'last_name' => str($email)->before('@')->toString(),
    ]);
    $student = Student::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2001-01-01',
    ]);
    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_id' => $student->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'intake_period_id' => $context['intakePeriod']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'application_tracking_number' => 'APP-'.strtoupper(str()->random(8)),
    ]);
    ClassList::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_application_id' => $studentApplication->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    $semesterId = $context['classConfig']->semester_id;

    if ($semesterId === null) {
        $semesterId = (int) Semester::query()->firstOrCreate(
            ['slug' => 'semester-1'],
            ['name' => 'Semester 1', 'description' => null],
        )->id;
    }

    $enrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $context['calendar']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'student_enrolment_status_id' => $enrolmentStatus->id,
    ]);

    ensureStudentSemesterPhaseForEnrolment($enrolment);

    return $studentApplication;
}
