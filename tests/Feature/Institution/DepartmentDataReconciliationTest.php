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
use Illuminate\Support\Str;

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

it('renders enrolment vs class list page for authorized users', function (): void {
    $context = makeDepartmentReconciliationContext();

    $this->get(route('department-data-reconciliation.enrolment-vs-class-list', [
        'department' => $context['institutionDepartment']->id,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/departments/reconciliation/EnrolmentVsClassList')
            ->has('calendarYear')
            ->has('department'));
});

it('downloads enrolment vs class list template', function (): void {
    $context = makeDepartmentReconciliationContext();

    $response = $this->get(route('department-data-reconciliation.enrolment-vs-class-list.template', [
        'department' => $context['institutionDepartment']->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('enrolment-vs-class-list');
});

it('returns department reconciliation counts', function (): void {
    $context = makeDepartmentReconciliationContext();

    createDepartmentReconciliationStudent($context, '26ENG1001HP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    $this->getJson(route('department-data-reconciliation.counts', [
        'department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]))
        ->assertSuccessful()
        ->assertJsonPath('enrolledThisYear', 1)
        ->assertJsonPath('calendarYear', $context['calendarYear']);
});

it('previews elevate and matched rows for enrolment vs class list', function (): void {
    $context = makeDepartmentReconciliationContext();

    createDepartmentReconciliationStudent($context, '26ENGMATCHHP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    createDepartmentReconciliationStudent($context, '26ENGELEVHP', [
        'createEnrolment' => false,
        'classListType' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $file = storeDepartmentReconciliationCsv(
        ['Student Number', 'Level', 'Course'],
        [
            ['26ENGMATCHHP', $context['levelName'], $context['courseName']],
            ['26ENGELEVHP', $context['levelName'], $context['courseName']],
            ['26ENGUNKNOWN', $context['levelName'], $context['courseName']],
        ],
    );

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.preview', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 3)
        ->assertJsonPath('summary.matched', 1)
        ->assertJsonPath('summary.elevate', 1)
        ->assertJsonPath('summary.notFound', 1)
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.status', 'matched')
        ->assertJsonPath('rows.1.status', 'elevate')
        ->assertJsonPath('rows.2.status', 'not_found');
});

it('flags extras on system that are missing from the physical list', function (): void {
    $context = makeDepartmentReconciliationContext();

    createDepartmentReconciliationStudent($context, '26ENGEXTRA1HP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    createDepartmentReconciliationStudent($context, '26ENGONFILEHP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    $file = storeDepartmentReconciliationCsv(
        ['Student Number', 'Level', 'Course'],
        [
            ['26ENGONFILEHP', $context['levelName'], $context['courseName']],
        ],
    );

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.preview', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.extras', 1)
        ->assertJsonPath('extras.0.studentNumber', '26ENGEXTRA1HP');
});

it('elevates selectable students without removing extras', function (): void {
    $context = makeDepartmentReconciliationContext();

    $extra = createDepartmentReconciliationStudent($context, '26ENGKEEP1HP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    $toElevate = createDepartmentReconciliationStudent($context, '26ENGMOVE1HP', [
        'createEnrolment' => false,
        'classListType' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 2,
                'studentApplicationId' => $toElevate['application']->id,
            ],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('summary.skipped', 0);

    expect(StudentEnrolment::query()
        ->where('student_application_id', $toElevate['application']->id)
        ->exists())->toBeTrue()
        ->and(StudentEnrolment::query()->whereKey($extra['enrolment']->id)->exists())->toBeTrue();
});

it('forbids view-only users from processing enrolment reconciliation', function (): void {
    $context = makeDepartmentReconciliationContext();
    actingAsDepartmentViewOnlyUser($context['tenantId']);

    $toElevate = createDepartmentReconciliationStudent($context, '26ENGVIEW1HP', [
        'createEnrolment' => false,
        'classListType' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 2,
                'studentApplicationId' => $toElevate['application']->id,
            ],
        ],
    ])->assertForbidden();
});

it('renders semester reconciliation page and downloads template', function (): void {
    $context = makeDepartmentReconciliationContext();

    $this->get(route('department-data-reconciliation.semester-reconciliation', [
        'department' => $context['institutionDepartment']->id,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/departments/reconciliation/SemesterReconciliation'));

    $response = $this->get(route('department-data-reconciliation.semester-reconciliation.template', [
        'department' => $context['institutionDepartment']->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('semester-reconciliation');
});

it('previews and processes semester phase mismatches', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGPHASE1HP', [
        'createEnrolment' => true,
        'classListType' => ClassListTypeEnum::FINAL->value,
    ]);

    $phases = ProgrammeSemester::query()
        ->where('department_level_course_id', $context['departmentLevelCourse']->id)
        ->orderBy('position')
        ->get();

    expect($phases)->toHaveCount(2);

    $targetPhase = $phases->last();

    $file = storeDepartmentReconciliationCsv(
        ['Student Number', 'Level', 'Course', 'Programme Phase'],
        [
            ['26ENGPHASE1HP', $context['levelName'], $context['courseName'], $targetPhase->name],
        ],
    );

    $preview = $this->postJson(route('department-data-reconciliation.semester-reconciliation.preview', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $preview->assertSuccessful()
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.mismatch', 1)
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.status', 'mismatch')
        ->assertJsonPath('rows.0.programmeSemesterId', $targetPhase->id);

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'rows' => [
            [
                'rowNumber' => (int) $preview->json('rows.0.rowNumber'),
                'studentEnrolmentId' => $created['enrolment']->id,
                'programmeSemesterId' => $targetPhase->id,
            ],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 1);

    $enrolment = $created['enrolment']->fresh(['studentSemesters']);
    $current = $enrolment->currentStudentSemester();

    expect($current?->programme_semester_id)->toBe($targetPhase->id)
        ->and((int) $enrolment->semester_id)->toBe((int) $current?->semester_id);
});
