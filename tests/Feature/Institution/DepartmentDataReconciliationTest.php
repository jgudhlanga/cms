<?php

declare(strict_types=1);

use App\Actions\Students\SetStudentEnrolmentCurrentPhaseAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Department;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;

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

it('refuses to elevate an applicant sitting on a failed class list', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGFAILED1HP', [
        'classListType' => ClassListTypeEnum::FAILED->value,
    ]);

    $response = $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1)
        ->assertJsonPath('rows.0.status', 'skipped');

    // Nothing may have been written: no enrolment, class list untouched, workflow step untouched.
    expect(StudentEnrolment::query()->where('student_application_id', $created['application']->id)->exists())
        ->toBeFalse()
        ->and(ClassList::query()->where('student_application_id', $created['application']->id)->first()?->type)
        ->toBe(ClassListTypeEnum::FAILED)
        ->and((int) $created['application']->fresh()->workflow_step_id)
        ->toBe((int) $created['application']->workflow_step_id);
});

it('refuses to elevate a rejected applicant', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGREJECT1HP');

    $rejectedStep = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::REJECTED->slug()],
        [
            'name' => WorkflowStepEnum::REJECTED->name(),
            'position' => WorkflowStepEnum::REJECTED->position(),
            'description' => WorkflowStepEnum::REJECTED->description(),
        ],
    );
    $created['application']->update(['workflow_step_id' => $rejectedStep->id]);

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1);

    expect(StudentEnrolment::query()->where('student_application_id', $created['application']->id)->exists())
        ->toBeFalse()
        ->and((int) $created['application']->fresh()->workflow_step_id)
        ->toBe((int) $rejectedStep->id);
});

it('refuses to elevate an applicant who has not cleared admissions', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGPENDING1HP');

    $pendingStep = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::REGISTRATION_FEE->slug()],
        [
            'name' => WorkflowStepEnum::REGISTRATION_FEE->name(),
            'position' => WorkflowStepEnum::REGISTRATION_FEE->position(),
            'description' => WorkflowStepEnum::REGISTRATION_FEE->description(),
        ],
    );
    $created['application']->update(['workflow_step_id' => $pendingStep->id]);

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1);

    expect(StudentEnrolment::query()->where('student_application_id', $created['application']->id)->exists())
        ->toBeFalse();
});

it('refuses to elevate an application belonging to another department', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGOTHER1HP', [
        'institutionDepartmentId' => (int) $context['otherInstitutionDepartment']->id,
    ]);

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1);

    expect(StudentEnrolment::query()->where('student_application_id', $created['application']->id)->exists())
        ->toBeFalse();
});

it('forbids a department scoped user from reconciling a department they are not assigned to', function (): void {
    $context = makeDepartmentReconciliationContext();

    actingAsDepartmentScopedUser(
        (int) $context['tenantId'],
        $context['institutionDepartment'],
    );

    // Their own department is reachable.
    $this->getJson(route('department-data-reconciliation.counts', [
        'department' => $context['institutionDepartment']->id,
    ]))->assertSuccessful();

    // A department they are not attached to is not.
    $this->getJson(route('department-data-reconciliation.counts', [
        'department' => $context['otherInstitutionDepartment']->id,
    ]))->assertForbidden();

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['otherInstitutionDepartment']->id,
    ]), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => 1],
        ],
    ])->assertForbidden();

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['otherInstitutionDepartment']->id,
    ]), [
        'rows' => [
            ['rowNumber' => 2, 'studentEnrolmentId' => 1, 'programmeSemesterId' => 1],
        ],
    ])->assertForbidden();
});

it('forbids view-only users from processing semester reconciliation', function (): void {
    $context = makeDepartmentReconciliationContext();

    actingAsDepartmentViewOnlyUser((int) $context['tenantId']);

    $this->postJson(route('department-data-reconciliation.semester-reconciliation.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'rows' => [
            ['rowNumber' => 2, 'studentEnrolmentId' => 1, 'programmeSemesterId' => 1],
        ],
    ])->assertForbidden();
});

it('makes a year 2 phase current even though it shares a calendar slot with year 1', function (): void {
    $context = makeDepartmentReconciliationContext();
    $offering = makeMultiYearOffering($context, years: 2);

    $phases = $offering['phases'];
    expect($phases)->toHaveCount(4);

    $yearOneSemOne = $phases[0];
    $yearOneSemTwo = $phases[1];
    $yearTwoSemOne = $phases[2];

    // Year 1 Sem 1 and Year 2 Sem 1 both map to the "semester-1" calendar slot.
    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $yearOneSemOne],
        ['slug' => 'semester-2', 'phase' => $yearOneSemTwo],
    ]);

    expect($enrolment->currentStudentSemester()?->programme_semester_id)->toBe($yearOneSemTwo->id);

    app(SetStudentEnrolmentCurrentPhaseAction::class)
        ->execute($enrolment, $yearTwoSemOne);

    $fresh = $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.programmeSemester']);

    // Before the phase-position fix this returned the Year 1 Sem 2 row and the move was a silent no-op.
    expect($fresh->currentStudentSemester()?->programme_semester_id)->toBe($yearTwoSemOne->id)
        ->and($fresh->studentSemesters)->toHaveCount(2);
});

it('refuses a backward phase move instead of silently doing nothing', function (): void {
    $context = makeDepartmentReconciliationContext();
    $offering = makeMultiYearOffering($context, years: 2);

    $phases = $offering['phases'];
    $yearOneSemOne = $phases[0];
    $yearOneSemTwo = $phases[1];

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $yearOneSemOne],
        ['slug' => 'semester-2', 'phase' => $yearOneSemTwo],
    ]);

    expect(fn () => app(SetStudentEnrolmentCurrentPhaseAction::class)
        ->execute($enrolment, $yearOneSemOne))
        ->toThrow(InvalidArgumentException::class);

    // The student is left exactly as they were.
    $fresh = $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.programmeSemester']);
    expect($fresh->currentStudentSemester()?->programme_semester_id)->toBe($yearOneSemTwo->id);
});

it('preserves a blocking enrolment status instead of forcing the student active', function (): void {
    $context = makeDepartmentReconciliationContext();
    $offering = makeMultiYearOffering($context, years: 2);

    $phases = $offering['phases'];

    $deferred = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Deferred'],
        ['description' => 'Test'],
    );
    StudentEnrolmentStatus::query()->firstOrCreate(['name' => 'Active'], ['description' => 'Test']);

    $enrolment = makeEnrolmentWithPhases(
        $context,
        $offering,
        [['slug' => 'semester-1', 'phase' => $phases[0]]],
        statusId: (int) $deferred->id,
    );

    app(SetStudentEnrolmentCurrentPhaseAction::class)
        ->execute($enrolment, $phases[1]);

    $fresh = $enrolment->fresh(['studentSemesters']);

    expect((int) $fresh->student_enrolment_status_id)->toBe((int) $deferred->id)
        ->and($fresh->currentStudentSemester()?->programme_semester_id)->toBe($phases[1]->id);
});

it('reports a clear reason rather than crashing when an attachment phase has no free calendar slot', function (): void {
    $context = makeDepartmentReconciliationContext();
    $offering = makeMultiYearOffering($context, years: 1, attachmentSemesters: 1);

    $phases = $offering['phases'];
    $attachment = $phases->last();

    expect($attachment->kind->value)->toBe('industrial_attachment');

    $enrolment = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $phases[0]],
    ]);

    // Previously this hit stu_sem_enrolment_semester_unq and surfaced as a generic row failure.
    expect(fn () => app(SetStudentEnrolmentCurrentPhaseAction::class)
        ->execute($enrolment, $attachment))
        ->toThrow(InvalidArgumentException::class);
});

it('files a back-year reconciliation against that year, not the current calendar', function (): void {
    $context = makeDepartmentReconciliationContext();
    $pastYear = $context['calendarYear'] - 1;

    $pastCalendar = AcademicCalendar::query()->create([
        'calendar_year' => (string) $pastYear,
        'type' => 'semester',
        'opening_date' => $pastYear.'-01-15',
        'closing_date' => $pastYear.'-11-30',
    ]);

    $created = createDepartmentReconciliationStudent($context, '26ENGBACKYEAR1HP');

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        'calendar_year' => $pastYear,
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 1);

    $enrolment = StudentEnrolment::query()
        ->where('student_application_id', $created['application']->id)
        ->first();

    // Before the fix this landed on the current-year calendar regardless of the year requested.
    expect($enrolment)->not->toBeNull()
        ->and((int) $enrolment->academic_calendar_id)->toBe((int) $pastCalendar->id)
        ->and((int) $enrolment->academic_calendar_id)->not->toBe((int) $context['calendar']->id);
});

it('skips a row when the requested year has no academic calendar', function (): void {
    $context = makeDepartmentReconciliationContext();

    $created = createDepartmentReconciliationStudent($context, '26ENGNOCAL1HP');

    $this->postJson(route('department-data-reconciliation.enrolment-vs-class-list.process', [
        'department' => $context['institutionDepartment']->id,
    ]), [
        // No calendar was seeded for this year.
        'calendar_year' => $context['calendarYear'] - 5,
        'rows' => [
            ['rowNumber' => 2, 'studentApplicationId' => $created['application']->id],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1);

    expect(StudentEnrolment::query()->where('student_application_id', $created['application']->id)->exists())
        ->toBeFalse();
});
