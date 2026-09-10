<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
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
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/*
 * Shared fixtures for the department Data Reconciliation feature.
 *
 * Lives here rather than inside a test file so more than one suite can use it without
 * require_once-ing another test (which is how duplicate function declarations creep in).
 */

function actingAsRootDepartmentReconciliationUser(): User
{
    Permission::findOrCreate('root:manage', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('root:manage');
    test()->actingAs($user);

    return $user;
}

/**
 * @return array{
 *     user: User,
 *     tenantId: int,
 *     institutionDepartment: InstitutionDepartment,
 *     otherInstitutionDepartment: InstitutionDepartment,
 *     calendar: AcademicCalendar,
 *     calendarYear: int,
 *     departmentLevel: DepartmentLevel,
 *     departmentCourse: DepartmentCourse,
 *     departmentLevelCourse: DepartmentLevelCourse,
 *     modeOfStudy: ModeOfStudy,
 *     levelName: string,
 *     courseName: string,
 * }
 */
function makeDepartmentReconciliationContext(): array
{
    $user = actingAsRootDepartmentReconciliationUser();
    $tenantId = (int) $user->tenant_id;
    $calendarYear = (int) now()->format('Y');

    $department = Department::factory()->create(['name' => 'Engineering '.uniqid()]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $department->id,
        'department_code' => 'ENG-'.uniqid(),
        'description' => 'Engineering',
    ]);

    $otherDepartment = Department::factory()->create(['name' => 'Tourism '.uniqid()]);
    $otherInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $otherDepartment->id,
        'department_code' => 'TOUR-'.uniqid(),
        'description' => 'Tourism',
    ]);

    foreach (['Semester 1', 'Semester 2'] as $name) {
        Semester::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );

    $calendar = AcademicCalendar::query()->firstOrCreate(
        [
            'calendar_year' => (string) $calendarYear,
            'type' => 'semester',
        ],
        [
            'opening_date' => now()->subDays(30)->toDateString(),
            'closing_date' => now()->addMonths(6)->toDateString(),
        ],
    );

    $levelName = 'ND1';
    $courseName = 'Civil Engineering';

    $course = Course::factory()->create(['name' => $courseName]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenantId,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create([
        'name' => $levelName,
        'calendar_type' => 'semester',
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenantId,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'duration_years' => 1,
        'taught_semester_count' => 2,
        'includes_industrial_attachment' => false,
        'attachment_semester_count' => 0,
    ]);

    app(SyncProgrammeSemestersForOfferingAction::class)->execute($departmentLevelCourse);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time '.uniqid()]);

    return [
        'user' => $user,
        'tenantId' => $tenantId,
        'institutionDepartment' => $institutionDepartment,
        'otherInstitutionDepartment' => $otherInstitutionDepartment,
        'calendar' => $calendar,
        'calendarYear' => $calendarYear,
        'departmentLevel' => $departmentLevel,
        'departmentCourse' => $departmentCourse,
        'departmentLevelCourse' => $departmentLevelCourse->fresh(['programmeSemesters']),
        'modeOfStudy' => $modeOfStudy,
        'levelName' => $levelName,
        'courseName' => $courseName,
    ];
}

/**
 * @param  array{
 *     classListType?: string|null,
 *     createEnrolment?: bool,
 *     createClassList?: bool,
 *     createAcceptedWorkflow?: bool,
 *     studentNumber?: string|null,
 *     institutionDepartmentId?: int|null,
 *     departmentLevelId?: int|null,
 *     departmentCourseId?: int|null,
 * }  $options
 * @return array{student: Student, application: StudentApplication|null, enrolment: StudentEnrolment|null}
 */
function createDepartmentReconciliationStudent(array $context, string $studentNumber, array $options = []): array
{
    $title = Title::query()->create(['name' => 'Mr '.uniqid()]);
    $gender = Gender::query()->create(['title' => 'Gender '.uniqid()]);
    $marital = MaritalStatus::query()->create(['title' => 'Single '.uniqid()]);
    $idType = IdType::query()->create(['name' => 'National ID '.uniqid()]);

    $studentUser = User::factory()->create([
        'tenant_id' => $context['tenantId'],
        'first_name' => 'Reconcile',
        'last_name' => 'Student',
    ]);

    $student = Student::query()->create([
        'tenant_id' => $context['tenantId'],
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-'.random_int(1000000, 9999999).'N63',
        'student_number' => $studentNumber,
        'date_of_birth' => '2001-01-01',
    ]);

    $institutionDepartmentId = $options['institutionDepartmentId'] ?? (int) $context['institutionDepartment']->id;
    $departmentLevelId = $options['departmentLevelId'] ?? (int) $context['departmentLevel']->id;
    $departmentCourseId = $options['departmentCourseId'] ?? (int) $context['departmentCourse']->id;

    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $context['tenantId'],
        'name' => 'Intake '.$student->id,
        'calendar_year' => (string) $context['calendarYear'],
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartmentId,
        'department_level_id' => $departmentLevelId,
        'department_course_id' => $departmentCourseId,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'application_tracking_number' => 'APP-'.strtoupper(uniqid()),
    ]);

    if ($options['createAcceptedWorkflow'] ?? true) {
        $acceptedStep = WorkflowStep::query()->firstOrCreate(
            ['slug' => WorkflowStepEnum::ACCEPTED->slug()],
            [
                'name' => WorkflowStepEnum::ACCEPTED->name(),
                'position' => WorkflowStepEnum::ACCEPTED->position(),
                'description' => WorkflowStepEnum::ACCEPTED->description(),
            ],
        );
        WorkflowStep::query()->firstOrCreate(
            ['slug' => WorkflowStepEnum::ENROLLED->slug()],
            [
                'name' => WorkflowStepEnum::ENROLLED->name(),
                'position' => WorkflowStepEnum::ENROLLED->position(),
                'description' => WorkflowStepEnum::ENROLLED->description(),
            ],
        );

        $studentApplication->update(['workflow_step_id' => $acceptedStep->id]);
    }

    if ($options['createClassList'] ?? true) {
        ClassList::query()->create([
            'tenant_id' => $context['tenantId'],
            'student_application_id' => $studentApplication->id,
            'type' => $options['classListType'] ?? ClassListTypeEnum::VERIFIED->value,
            'attributes' => [],
        ]);
    }

    $enrolment = null;

    if ($options['createEnrolment'] ?? false) {
        $semester = Semester::query()->firstOrCreate(
            ['slug' => 'semester-1'],
            ['name' => 'Semester 1', 'description' => null],
        );
        $enrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Active'],
            ['description' => 'Test'],
        );

        $programmeSemester = ProgrammeSemester::query()
            ->where('department_level_course_id', $context['departmentLevelCourse']->id)
            ->orderBy('position')
            ->first();

        $enrolment = StudentEnrolment::query()->create([
            'student_id' => $student->id,
            'student_application_id' => $studentApplication->id,
            'institution_department_id' => $institutionDepartmentId,
            'department_level_id' => $departmentLevelId,
            'department_course_id' => $departmentCourseId,
            'semester_id' => $semester->id,
            'academic_calendar_id' => $context['calendar']->id,
            'mode_of_study_id' => $context['modeOfStudy']->id,
            'student_enrolment_status_id' => $enrolmentStatus->id,
        ]);

        // Observer syncs student_semesters; pin programme phase for reconciliation comparisons.
        if ($programmeSemester instanceof ProgrammeSemester) {
            StudentSemester::query()
                ->where('student_enrolment_id', $enrolment->id)
                ->where('semester_id', $semester->id)
                ->update(['programme_semester_id' => $programmeSemester->id]);
        }
    }

    return [
        'student' => $student->fresh(),
        'application' => $studentApplication->fresh(),
        'enrolment' => $enrolment?->fresh(['studentSemesters']),
    ];
}

/**
 * @param  list<list<string|null>>  $rows
 */
function storeDepartmentReconciliationCsv(array $headers, array $rows): UploadedFile
{
    $relativePath = 'test-department-reconciliation-'.uniqid().'.csv';
    $fullPath = storage_path('app/'.$relativePath);
    $handle = fopen($fullPath, 'w');
    fputcsv($handle, $headers);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return new UploadedFile($fullPath, 'department-reconciliation.csv', 'text/csv', null, true);
}

function actingAsDepartmentViewOnlyUser(?int $tenantId = null): User
{
    Permission::findOrCreate('view:department-metadata', 'web');

    $user = User::factory()->create($tenantId !== null ? ['tenant_id' => $tenantId] : []);
    $user->givePermissionTo('view:department-metadata');
    test()->actingAs($user);

    return $user;
}

/**
 * A department-scoped user: holds the update permission but is restricted to the departments
 * their staff profile is attached to.
 */
function actingAsDepartmentScopedUser(int $tenantId, ?InstitutionDepartment $ownDepartment = null): User
{
    Permission::findOrCreate('update:department-metadata', 'web');
    Permission::findOrCreate('viewOnlyOwnDepartment:departments', 'web');

    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $user->givePermissionTo('update:department-metadata');
    $user->givePermissionTo('viewOnlyOwnDepartment:departments');

    $staff = Staff::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'employee_number' => 'EMP-'.strtoupper(uniqid()),
        'title_id' => Title::query()->create(['name' => 'Mr '.uniqid()])->id,
        'gender_id' => Gender::query()->create(['title' => 'Gender '.uniqid()])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single '.uniqid()])->id,
    ]);

    if ($ownDepartment instanceof InstitutionDepartment) {
        $staff->institutionDepartments()->attach($ownDepartment->id);
    }

    test()->actingAs($user);

    return $user;
}

/**
 * Builds a second offering in the same department with the given duration, so tests can reach
 * Year 2+ phases (the one-year offering in the base context cannot).
 *
 * @return array{offering: DepartmentLevelCourse, departmentLevel: DepartmentLevel, departmentCourse: DepartmentCourse, phases: Collection<int, ProgrammeSemester>}
 */
function makeMultiYearOffering(array $context, int $years = 2, int $attachmentSemesters = 0): array
{
    $course = Course::factory()->create(['name' => 'Mechanical Engineering '.uniqid()]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $context['tenantId'],
        'institution_department_id' => $context['institutionDepartment']->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create([
        'name' => 'HND'.uniqid(),
        'calendar_type' => 'semester',
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenantId'],
        'institution_department_id' => $context['institutionDepartment']->id,
        'level_id' => $level->id,
    ]);

    $offering = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'duration_years' => $years,
        'taught_semester_count' => $years * 2,
        'includes_industrial_attachment' => $attachmentSemesters > 0,
        'attachment_semester_count' => $attachmentSemesters,
    ]);

    app(SyncProgrammeSemestersForOfferingAction::class)->execute($offering);

    return [
        'offering' => $offering->fresh(['programmeSemesters']),
        'departmentLevel' => $departmentLevel,
        'departmentCourse' => $departmentCourse,
        'phases' => ProgrammeSemester::query()
            ->where('department_level_course_id', $offering->id)
            ->orderBy('position')
            ->get(),
    ];
}

/**
 * Creates an enrolment on the given offering holding exactly the supplied phase pins, bypassing
 * the sync observer so the test controls the starting state precisely.
 *
 * @param  list<array{slug: string, phase: ProgrammeSemester}>  $phaseRows
 */
function makeEnrolmentWithPhases(array $context, array $offering, array $phaseRows, ?int $statusId = null): StudentEnrolment
{
    $created = createDepartmentReconciliationStudent($context, '26ENG'.strtoupper(substr(uniqid(), -8)), [
        'departmentLevelId' => (int) $offering['departmentLevel']->id,
        'departmentCourseId' => (int) $offering['departmentCourse']->id,
    ]);

    $status = StudentEnrolmentStatus::query()->firstOrCreate(['name' => 'Active'], ['description' => 'Test']);
    $firstSemester = Semester::query()->firstOrCreate(['slug' => 'semester-1'], ['name' => 'Semester 1', 'description' => null]);

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $created['student']->id,
        'student_application_id' => $created['application']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $offering['departmentLevel']->id,
        'department_course_id' => $offering['departmentCourse']->id,
        'semester_id' => $firstSemester->id,
        'academic_calendar_id' => $context['calendar']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'student_enrolment_status_id' => $statusId ?? $status->id,
    ]);

    // Replace whatever the observer synced with the exact rows this test wants.
    StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->forceDelete();

    foreach ($phaseRows as $row) {
        $semester = Semester::query()->firstOrCreate(
            ['slug' => $row['slug']],
            ['name' => Str::headline($row['slug']), 'description' => null],
        );

        StudentSemester::query()->create([
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semester->id,
            'programme_semester_id' => $row['phase']->id,
            'student_enrolment_status_id' => $statusId ?? $status->id,
            'course_syllabus_ids' => [],
        ]);
    }

    return $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.programmeSemester']);
}
