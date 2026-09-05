<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\ClassConfigKindEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Str;

function reassignRootUser(?int $tenantId = null): User
{
    $user = User::factory()->create($tenantId !== null ? ['tenant_id' => $tenantId] : []);
    $user->givePermissionTo('root:manage');

    return $user;
}

function reassignStaffUser(int $tenantId): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $user->givePermissionTo('update:student-applications');

    return $user;
}

function reviewApplicationForReassign(string $studentNumber): StudentApplication
{
    $application = createVerifiedStudentApplication($studentNumber);
    $application->update([
        'workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::REVIEW)->id,
    ]);
    IntakePeriod::query()->whereKey($application->intake_period_id)->update(['is_active' => true]);

    return $application->fresh();
}

/**
 * @return array{
 *     department: InstitutionDepartment,
 *     level: DepartmentLevel,
 *     course: DepartmentCourse,
 *     mode: ModeOfStudy,
 *     dlc: DepartmentLevelCourse|null
 * }
 */
function createSisterDepartmentOffering(StudentApplication $source, bool $linkLevelAndMode = true, ?ModeOfStudy $mode = null): array
{
    $tenant = Tenant::query()->firstOrFail();
    $source->loadMissing(['institutionDepartment', 'departmentCourse', 'departmentLevel']);

    $department = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => Department::factory()->create()->id,
        'department_code' => 'SIS-'.strtoupper(Str::random(5)),
        'description' => 'Sister department for reassignment',
    ]);
    $level = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $department->id,
        'level_id' => $source->departmentLevel->level_id,
    ]);
    $course = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $department->id,
        'course_id' => $source->departmentCourse->course_id,
        'show_on_current_application_period' => true,
    ]);
    $mode ??= ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);
    $dlc = null;

    if ($linkLevelAndMode) {
        $dlc = DepartmentLevelCourse::query()->firstOrCreate([
            'department_course_id' => $course->id,
            'department_level_id' => $level->id,
        ]);
        ensureProgrammeOffering((int) $course->id, (int) $level->id, (int) $mode->id);
        $dlc = $dlc->fresh();
    }

    return compact('department', 'level', 'course', 'mode', 'dlc');
}

function taughtProgrammeSemester(DepartmentLevelCourse $dlc, int $position = 1): ProgrammeSemester
{
    return ProgrammeSemester::query()->firstOrCreate(
        [
            'department_level_course_id' => $dlc->id,
            'position' => $position,
        ],
        [
            'name' => 'Year 1 Sem '.$position,
            'kind' => ProgrammeSemesterKindEnum::TAUGHT,
        ],
    );
}

function semesterOne(): Semester
{
    return Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
}

function createEnrolmentForReassign(StudentApplication $application): StudentEnrolment
{
    return StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'semester_id' => semesterOne()->id,
        'academic_calendar_id' => AcademicCalendar::query()->create([
            'calendar_year' => '2099',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'opening_date' => '2099-01-01',
            'closing_date' => '2099-12-31',
        ])->id,
        'student_enrolment_status_id' => StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Active'],
            ['description' => 'Test'],
        )->id,
    ]);
}

function reassignPayload(StudentApplication|array $target, array $ids = []): array
{
    if ($target instanceof StudentApplication) {
        $fields = [
            'institution_department_id' => $target->institution_department_id,
            'department_level_id' => $target->department_level_id,
            'department_course_id' => $target->department_course_id,
            'mode_of_study_id' => $target->mode_of_study_id,
        ];
    } else {
        $fields = $target;
    }

    return [
        'application_ids' => $ids['application_ids'] ?? [],
        'student_enrolment_ids' => $ids['student_enrolment_ids'] ?? [],
        ...$fields,
    ];
}

function assertOfferingFields(StudentApplication|StudentEnrolment $record, array $expected): void
{
    expect((int) $record->institution_department_id)->toBe((int) $expected['institution_department_id'])
        ->and((int) $record->department_level_id)->toBe((int) $expected['department_level_id'])
        ->and((int) $record->department_course_id)->toBe((int) $expected['department_course_id'])
        ->and((int) $record->mode_of_study_id)->toBe((int) $expected['mode_of_study_id']);
}

it('lists offering usage for staff who can view applications', function (): void {
    $application = createVerifiedStudentApplication('REAS-USAGE-'.strtoupper(Str::random(4)));
    $application->loadMissing([
        'institutionDepartment.department',
        'departmentLevel.level',
        'departmentCourse.course',
        'modeOfStudy',
    ]);
    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $user->givePermissionTo('viewAny:student-applications');

    $this->actingAs($user)
        ->getJson(route('students.programmes.usage', [
            'department_course_id' => $application->department_course_id,
            'department_level_id' => $application->department_level_id,
            'mode_of_study_ids' => [$application->mode_of_study_id],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.application_id', $application->id)
        ->assertJsonPath('data.0.has_enrolment', false)
        ->assertJsonPath('data.0.institution_department_id', $application->institution_department_id)
        ->assertJsonPath('data.0.department', $application->institutionDepartment?->department?->name)
        ->assertJsonPath('data.0.department_level_id', $application->department_level_id)
        ->assertJsonPath('data.0.level', $application->departmentLevel?->level?->name)
        ->assertJsonPath('data.0.department_course_id', $application->department_course_id)
        ->assertJsonPath('data.0.course', $application->departmentCourse?->course?->name)
        ->assertJsonPath('data.0.mode_of_study_id', $application->mode_of_study_id)
        ->assertJsonPath('data.0.mode_of_study', $application->modeOfStudy?->name);
});

it('lists usage for selected application and enrolment ids', function (): void {
    $application = createVerifiedStudentApplication('REAS-USAGE-IDS-'.strtoupper(Str::random(4)));
    $enrolment = createEnrolmentForReassign($application);
    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $user->givePermissionTo('viewAny:student-applications');

    $this->actingAs($user)
        ->getJson(route('students.programmes.usage', [
            'application_ids' => [$application->id],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.application_id', $application->id)
        ->assertJsonPath('data.0.student_enrolment_id', $enrolment->id);

    $this->actingAs($user)
        ->getJson(route('students.programmes.usage', [
            'student_enrolment_ids' => [$enrolment->id],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.application_id', $application->id)
        ->assertJsonPath('data.0.has_enrolment', true);
});

it('moves the full student graph onto the target offering and leaves a control student untouched', function (): void {
    $application = createVerifiedStudentApplication('REAS-GRAPH-'.strtoupper(Str::random(4)));
    $control = createVerifiedStudentApplication('REAS-CTRL-'.strtoupper(Str::random(4)));
    $control->update([
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $application->mode_of_study_id,
    ]);

    $sourceDlc = DepartmentLevelCourse::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->firstOrFail();
    $sourceProgrammeSemester = taughtProgrammeSemester($sourceDlc);
    $sourceSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $application->tenant_id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_course_id' => $sourceDlc->id,
        'title' => 'Source syllabus',
        'code' => 'SRC-'.strtoupper(Str::random(4)),
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $enrolmentA = createEnrolmentForReassign($application);
    $enrolmentB = createEnrolmentForReassign($application);
    $enrolmentA->update(['course_syllabus_ids' => [$sourceSyllabus->id]]);
    $enrolmentB->update(['course_syllabus_ids' => [$sourceSyllabus->id]]);

    foreach ([$enrolmentA, $enrolmentB] as $enrolment) {
        $semester = StudentSemester::query()->firstOrCreate(
            [
                'student_enrolment_id' => $enrolment->id,
                'semester_id' => semesterOne()->id,
            ],
            [
                'programme_semester_id' => $sourceProgrammeSemester->id,
                'student_enrolment_status_id' => $enrolment->student_enrolment_status_id,
                'course_syllabus_ids' => [$sourceSyllabus->id],
            ],
        );
        $semester->update([
            'programme_semester_id' => $sourceProgrammeSemester->id,
            'course_syllabus_ids' => [$sourceSyllabus->id],
        ]);
    }

    $sourceClassConfig = ClassConfig::query()->create([
        'calendar_year' => '2099',
        'semester_id' => semesterOne()->id,
        'programme_semester_id' => $sourceProgrammeSemester->id,
        'name' => 'Source class',
        'kind' => ClassConfigKindEnum::STANDARD,
        'slug' => 'standard',
        'institution_department_id' => $application->institution_department_id,
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'students_per_class' => 20,
        'course_syllabus_ids' => [$sourceSyllabus->id],
    ]);
    $sourceClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $application->tenant_id,
        'class_config_id' => $sourceClassConfig->id,
        'name' => 'SRC-1',
    ]);
    $livePivot = AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $application->tenant_id,
        'academic_calendar_class_id' => $sourceClass->id,
        'student_enrolment_id' => $enrolmentA->id,
        'student_semesters_id' => StudentSemester::query()
            ->where('student_enrolment_id', $enrolmentA->id)
            ->value('id'),
        'is_live' => true,
    ]);

    $matchingResult = StudentExamResult::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'candidate_number' => 'CAND-MOVE',
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);
    $otherOfferingResult = StudentExamResult::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'candidate_number' => 'CAND-KEEP',
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => DepartmentCourse::query()->create([
            'tenant_id' => $application->tenant_id,
            'institution_department_id' => $application->institution_department_id,
            'course_id' => Course::factory()->create()->id,
        ])->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'calendar_year' => 2025,
        'session' => '2025-11-01',
        'comment' => 'PROCEED',
    ]);

    $application->student->update(['student_number_generated' => true]);
    $previousStudentNumber = $application->student->student_number;
    $classListId = ClassList::query()->where('student_application_id', $application->id)->value('id');
    $sourceOffering = [
        'institution_department_id' => (int) $application->institution_department_id,
        'department_level_id' => (int) $application->department_level_id,
        'department_course_id' => (int) $application->department_course_id,
        'mode_of_study_id' => (int) $application->mode_of_study_id,
    ];

    $target = createSisterDepartmentOffering($application);
    $targetDlc = $target['dlc'];
    expect($targetDlc)->not->toBeNull();
    $targetProgrammeSemester = taughtProgrammeSemester($targetDlc);
    $targetSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $application->tenant_id,
        'institution_department_id' => $target['department']->id,
        'department_level_course_id' => $targetDlc->id,
        'title' => 'Target syllabus',
        'code' => 'TGT-'.strtoupper(Str::random(4)),
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);
    $targetClassConfig = ClassConfig::query()->create([
        'calendar_year' => '2099',
        'semester_id' => semesterOne()->id,
        'programme_semester_id' => $targetProgrammeSemester->id,
        'name' => 'Target class',
        'kind' => ClassConfigKindEnum::STANDARD,
        'slug' => 'standard',
        'institution_department_id' => $target['department']->id,
        'department_course_id' => $target['course']->id,
        'department_level_id' => $target['level']->id,
        'mode_of_study_id' => $target['mode']->id,
        'students_per_class' => 20,
        'course_syllabus_ids' => [$targetSyllabus->id],
    ]);
    $targetClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $application->tenant_id,
        'class_config_id' => $targetClassConfig->id,
        'name' => 'TGT-1',
    ]);

    $expected = [
        'institution_department_id' => $target['department']->id,
        'department_level_id' => $target['level']->id,
        'department_course_id' => $target['course']->id,
        'mode_of_study_id' => $target['mode']->id,
    ];

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('students.programmes.reassign'), reassignPayload($expected, [
            'application_ids' => [$application->id],
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $application->refresh();
    $enrolmentA->refresh();
    $enrolmentB->refresh();
    $control->refresh();
    $matchingResult->refresh();
    $otherOfferingResult->refresh();
    $livePivot->refresh();

    assertOfferingFields($application, $expected);
    assertOfferingFields($enrolmentA, $expected);
    assertOfferingFields($enrolmentB, $expected);
    assertOfferingFields($control, $sourceOffering);

    $mappedProgrammeSemesterIds = StudentSemester::query()
        ->whereIn('student_enrolment_id', [$enrolmentA->id, $enrolmentB->id])
        ->whereNotNull('programme_semester_id')
        ->pluck('programme_semester_id')
        ->map(fn ($id): int => (int) $id)
        ->all();
    $targetProgrammeSemesterIds = ProgrammeSemester::query()
        ->where('department_level_course_id', $targetDlc->id)
        ->pluck('id')
        ->map(fn ($id): int => (int) $id)
        ->all();

    expect($mappedProgrammeSemesterIds)->not->toBeEmpty();
    foreach ($mappedProgrammeSemesterIds as $programmeSemesterId) {
        expect($targetProgrammeSemesterIds)->toContain($programmeSemesterId);
    }

    expect($enrolmentA->course_syllabus_ids)->toContain($targetSyllabus->id)
        ->and((int) $livePivot->academic_calendar_class_id)->toBe((int) $targetClass->id)
        ->and($livePivot->is_live)->toBeTrue()
        ->and((int) $matchingResult->department_course_id)->toBe((int) $target['course']->id)
        ->and((int) $matchingResult->institution_department_id)->toBe((int) $target['department']->id)
        ->and((int) $otherOfferingResult->department_course_id)->not->toBe((int) $target['course']->id)
        ->and(ClassList::query()->whereKey($classListId)->value('student_application_id'))->toBe($application->id)
        ->and($application->student->fresh()->student_number)->not->toBe($previousStudentNumber);
});

it('does not create an enrolment when reassigning an application-only record', function (): void {
    $application = createVerifiedStudentApplication('REAS-APPONLY-'.strtoupper(Str::random(4)));
    $target = createSisterDepartmentOffering($application);

    expect(StudentEnrolment::query()->where('student_application_id', $application->id)->count())->toBe(0);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(StudentEnrolment::query()->where('student_application_id', $application->id)->count())->toBe(0);
    assertOfferingFields($application->fresh(), [
        'institution_department_id' => $target['department']->id,
        'department_level_id' => $target['level']->id,
        'department_course_id' => $target['course']->id,
        'mode_of_study_id' => $target['mode']->id,
    ]);
});

it('updates the application when the request identifies an enrolment', function (): void {
    $application = createVerifiedStudentApplication('REAS-ENRID-'.strtoupper(Str::random(4)));
    $enrolment = createEnrolmentForReassign($application);
    $target = createSisterDepartmentOffering($application);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['student_enrolment_ids' => [$enrolment->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $expected = [
        'institution_department_id' => $target['department']->id,
        'department_level_id' => $target['level']->id,
        'department_course_id' => $target['course']->id,
        'mode_of_study_id' => $target['mode']->id,
    ];
    assertOfferingFields($application->fresh(), $expected);
    assertOfferingFields($enrolment->fresh(), $expected);
});

it('rejects another department’s level and course ids', function (): void {
    $application = createVerifiedStudentApplication('REAS-XDEPT-'.strtoupper(Str::random(4)));
    $sister = createSisterDepartmentOffering($application);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $sister['department']->id,
            'department_level_id' => $application->department_level_id,
            'department_course_id' => $application->department_course_id,
            'mode_of_study_id' => $sister['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertSessionHasErrors('institution_department_id');
});

it('rejects an unlinked course and level', function (): void {
    $application = createVerifiedStudentApplication('REAS-UNLINK-'.strtoupper(Str::random(4)));
    $sister = createSisterDepartmentOffering($application, linkLevelAndMode: false);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $sister['department']->id,
            'department_level_id' => $sister['level']->id,
            'department_course_id' => $sister['course']->id,
            'mode_of_study_id' => $sister['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertSessionHasErrors('department_course_id');
});

it('adds a missing destination mode so students can move before course modes are saved', function (): void {
    $application = createVerifiedStudentApplication('REAS-CATCH22-'.strtoupper(Str::random(4)));
    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);
    $blockRelease = ModeOfStudy::query()->firstOrCreate(
        ['name' => 'Block Release'],
        ['description' => 'Block Release'],
    );
    $application->update(['mode_of_study_id' => $blockRelease->id]);
    CourseLevelMode::query()->updateOrCreate(
        [
            'department_course_id' => $application->department_course_id,
            'department_level_id' => $application->department_level_id,
        ],
        ['modes' => [(int) $blockRelease->id]],
    );

    $user = reassignRootUser($application->tenant_id);
    $user->givePermissionTo(['view:department-metadata', 'update:department-metadata']);
    $this->actingAs($user);

    $this->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('department-courses.modes.store', $application->department_course_id), [
            'department_course_id' => $application->department_course_id,
            'mode_ids' => [
                (string) $application->department_level_id => [$fullTime->id],
            ],
        ])
        ->assertSessionHasErrors('mode_ids');

    $this->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $application->institution_department_id,
            'department_level_id' => $application->department_level_id,
            'department_course_id' => $application->department_course_id,
            'mode_of_study_id' => $fullTime->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((int) $application->fresh()->mode_of_study_id)->toBe((int) $fullTime->id);

    $row = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->first();

    expect(collect($row?->modes)->map(fn ($id): int => (int) $id)->all())
        ->toContain((int) $fullTime->id)
        ->toContain((int) $blockRelease->id);

    $this->post(route('department-courses.modes.store', $application->department_course_id), [
        'department_course_id' => $application->department_course_id,
        'mode_ids' => [
            (string) $application->department_level_id => [$fullTime->id],
        ],
    ])->assertSuccessful();

    $row = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->first();

    expect(collect($row?->modes)->map(fn ($id): int => (int) $id)->all())->toBe([(int) $fullTime->id]);
});

it('writes the target department course id when the catalogue course name matches', function (): void {
    $application = createVerifiedStudentApplication('REAS-SAMENAME-'.strtoupper(Str::random(4)));
    $sister = createSisterDepartmentOffering($application);

    expect($sister['course']->course_id)->toBe($application->departmentCourse->course_id)
        ->and($sister['course']->id)->not->toBe($application->department_course_id);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $sister['department']->id,
            'department_level_id' => $sister['level']->id,
            'department_course_id' => $sister['course']->id,
            'mode_of_study_id' => $sister['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((int) $application->fresh()->department_course_id)->toBe((int) $sister['course']->id);
});

it('unassigns class membership when the target offering has no matching class', function (): void {
    $application = createVerifiedStudentApplication('REAS-UNCLASS-'.strtoupper(Str::random(4)));
    $enrolment = createEnrolmentForReassign($application);
    $sourceClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $application->tenant_id,
        'class_config_id' => ClassConfig::query()->create([
            'calendar_year' => '2099',
            'semester_id' => semesterOne()->id,
            'name' => 'Only source class',
            'kind' => ClassConfigKindEnum::STANDARD,
            'slug' => 'standard',
            'institution_department_id' => $application->institution_department_id,
            'department_course_id' => $application->department_course_id,
            'department_level_id' => $application->department_level_id,
            'mode_of_study_id' => $application->mode_of_study_id,
            'students_per_class' => 20,
        ])->id,
        'name' => 'SRC-ONLY',
    ]);
    $pivot = AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $application->tenant_id,
        'academic_calendar_class_id' => $sourceClass->id,
        'student_enrolment_id' => $enrolment->id,
        'is_live' => true,
    ]);
    $target = createSisterDepartmentOffering($application);

    $this->actingAs(reassignRootUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $pivot->refresh();
    expect($pivot->is_live)->toBeFalse()
        ->and($pivot->concluded_at)->not->toBeNull();
});

it('unblocks dropping a mode after linked students are moved off it', function (): void {
    $application = createVerifiedStudentApplication('REAS-UNBLOCK-'.strtoupper(Str::random(4)));
    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);
    $blockRelease = ModeOfStudy::query()->firstOrCreate(
        ['name' => 'Block Release'],
        ['description' => 'Block Release'],
    );
    $application->update(['mode_of_study_id' => $blockRelease->id]);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $fullTime->id, (int) $blockRelease->id],
    ]);

    $user = reassignRootUser($application->tenant_id);
    $user->givePermissionTo(['view:department-metadata', 'update:department-metadata']);
    $this->actingAs($user);

    $this->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('department-courses.modes.store', $application->department_course_id), [
            'department_course_id' => $application->department_course_id,
            'mode_ids' => [
                (string) $application->department_level_id => [$fullTime->id],
            ],
        ])
        ->assertSessionHasErrors('mode_ids');

    $this->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $application->institution_department_id,
            'department_level_id' => $application->department_level_id,
            'department_course_id' => $application->department_course_id,
            'mode_of_study_id' => $fullTime->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->post(route('department-courses.modes.store', $application->department_course_id), [
        'department_course_id' => $application->department_course_id,
        'mode_ids' => [
            (string) $application->department_level_id => [$fullTime->id],
        ],
    ])->assertSuccessful();

    $row = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->first();

    expect(collect($row?->modes)->map(fn ($id): int => (int) $id)->all())->toBe([(int) $fullTime->id]);
});

it('forbids staff from reassigning an accepted application', function (): void {
    $application = createVerifiedStudentApplication('REAS-ACC-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($application->intake_period_id)->update(['is_active' => true]);
    $target = createSisterDepartmentOffering($application);

    $this->actingAs(reassignStaffUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertSessionHasErrors('application_ids');

    expect((int) $application->fresh()->institution_department_id)->toBe((int) $application->institution_department_id);
});

it('forbids staff from reassigning an inactive intake application', function (): void {
    $application = reviewApplicationForReassign('REAS-INACT-'.strtoupper(Str::random(4)));
    $application->update([
        'intake_period_id' => IntakePeriod::query()->create([
            'tenant_id' => $application->tenant_id,
            'name' => 'Past Intake '.Str::random(4),
            'start_date' => now()->subYear()->startOfMonth()->toDateString(),
            'end_date' => now()->subYear()->endOfMonth()->toDateString(),
            'is_active' => false,
        ])->id,
    ]);
    $target = createSisterDepartmentOffering($application);

    $this->actingAs(reassignStaffUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertSessionHasErrors('application_ids');
});

it('allows staff to reassign a review application in an active intake', function (): void {
    $application = reviewApplicationForReassign('REAS-REVIEW-'.strtoupper(Str::random(4)));
    $target = createSisterDepartmentOffering($application);

    $this->actingAs(reassignStaffUser($application->tenant_id))
        ->from('/')
        ->post(route('students.programmes.reassign'), reassignPayload([
            'institution_department_id' => $target['department']->id,
            'department_level_id' => $target['level']->id,
            'department_course_id' => $target['course']->id,
            'mode_of_study_id' => $target['mode']->id,
        ], ['application_ids' => [$application->id]]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((int) $application->fresh()->department_course_id)->toBe((int) $target['course']->id);
});
