<?php

declare(strict_types=1);

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\ModeOfStudy;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentClearance;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentExamResult;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Institution\InstitutionFeatureService;
use App\Services\Students\StudentExamResultAccessService;
use App\Services\Students\StudentExamResultLookupService;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    (new RolesTableSeeder)->run();
});

function createPortalExamStudent(array $overrides = []): array
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $suffix = uniqid();

    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Permission::findOrCreate('viewOwnExamResults:students', 'web');
    $user->givePermissionTo('viewOwnExamResults:students');

    $studentRole = Role::findByName('Student', 'web');
    if ($studentRole) {
        $user->assignRole($studentRole);
    }

    $idType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->label()],
        ['id' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()]
    );

    $student = Student::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => Title::query()->create(['name' => 'Mr ER '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male ER '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single ER '.$suffix])->id,
        'id_type_id' => $idType->id,
        'passport_number' => 'P'.strtoupper(substr($suffix, -8)),
        'student_number' => 'H'.strtoupper(substr($suffix, -6)),
        'date_of_birth' => '2001-01-01',
    ], $overrides));

    return compact('tenant', 'user', 'student');
}

/**
 * @return array{surname: string|null, first_names: string|null}
 */
function examinationIdentityForStudent(Student $student, bool $withMiddleName = true): array
{
    $student->loadMissing('user');

    $firstNames = trim(implode(' ', array_filter([
        $student->user?->first_name,
        $withMiddleName ? $student->user?->middle_name : null,
    ])));

    return [
        'surname' => $student->user?->last_name,
        'first_names' => $firstNames !== '' ? $firstNames : $student->user?->first_name,
    ];
}

function mockPortalExamResultAccess(): void
{
    test()->mock(StudentExamResultAccessService::class, function ($mock): void {
        $mock->shouldReceive('evaluate')->andReturn([
            'canViewResults' => true,
            'gate' => 'fees',
            'allowOnlineClearance' => false,
            'fees' => [
                'tuition' => 100,
                'autoCardFee' => 0,
                'partTimeLevy' => 0,
                'expectedTotal' => 100,
                'paidFromBank' => 100,
                'paidFromLedger' => 0,
                'paidTotal' => 100,
                'outstanding' => 0,
                'isFullyPaid' => true,
                'breakdown' => [],
                'hasStudentNumber' => true,
                'source' => 'enrolment',
            ],
            'clearance' => null,
            'idValidation' => ['required' => false, 'isValid' => true, 'needsCorrection' => false],
            'academicCalendarId' => null,
            'semesterId' => null,
        ]);
        $mock->shouldReceive('resolveEnrolmentContext')->andReturn(null);
    });
}

test('guest cannot access portal exam results', function () {
    $this->get(route('portal.exam-results'))->assertRedirect();
});

test('guest cannot lookup portal exam results', function () {
    $this->post(route('portal.exam-results.lookup'), [
        'candidate_number' => 'H0001',
    ])->assertRedirect();
});

test('guest cannot show a saved exam result', function () {
    $this->get(route('portal.exam-results.show', 1))->assertRedirect();
});

test('hasUnclaimedSession is true only when an exam session is not yet recorded', function () {
    ['student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'UNCLAIM1',
    ]);

    ExaminationResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'UNCLAIM1',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'UNCLAIM1',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2025-11-01',
        'course_comment' => 'PROCEED',
    ]);

    $service = app(StudentExamResultLookupService::class);

    expect($service->hasUnclaimedSession($student))->toBeTrue();

    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'UNCLAIM1',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    expect($service->hasUnclaimedSession($student))->toBeTrue();

    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'UNCLAIM1',
        'calendar_year' => 2025,
        'session' => '2025-11-01',
        'comment' => 'PROCEED',
    ]);

    expect($service->hasUnclaimedSession($student))->toBeFalse();
});

test('listForStudent returns only that students saved results ordered newest first', function () {
    ['tenant' => $tenant, 'student' => $student] = createPortalExamStudent();
    $other = createPortalExamStudent()['student'];

    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-A',
        'calendar_year' => 2025,
        'session' => '2025-06-01',
        'comment' => 'AWARD',
    ]);
    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-B',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'PROCEED',
    ]);
    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $other->id,
        'candidate_number' => 'CAND-OTHER',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    $list = app(StudentExamResultLookupService::class)->listForStudent($student);

    expect($list)->toHaveCount(2)
        ->and($list->first()->calendar_year)->toBe(2026)
        ->and($list->pluck('candidate_number')->all())->not->toContain('CAND-OTHER');
});

test('showForStudent aborts when result belongs to another student', function () {
    ['student' => $owner] = createPortalExamStudent();
    ['student' => $intruder] = createPortalExamStudent();

    $result = StudentExamResult::query()->create([
        'tenant_id' => $owner->tenant_id,
        'student_id' => $owner->id,
        'candidate_number' => 'CAND-OWN',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    app(StudentExamResultLookupService::class)->showForStudent($intruder, $result);
})->throws(HttpException::class);

test('student index includes saved exam results', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent();

    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-IDX',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    $this->actingAs($user)
        ->get(route('portal.exam-results'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResults')
            ->has('savedResults', 1)
            ->where('savedResults.0.candidateNumber', 'CAND-IDX')
        );
});

test('student can open own saved session show page', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent();

    $result = StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-SHOW',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    ExaminationResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-SHOW',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'course_comment' => 'AWARD',
    ]);

    $this->actingAs($user)
        ->get(route('portal.exam-results.show', $result))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResultShow')
            ->where('summary.candidateNumber', 'CAND-SHOW')
            ->where('summary.session', '2026-06-01')
        );
});

test('student cannot open another students saved session', function () {
    ['student' => $owner, 'tenant' => $tenant] = createPortalExamStudent();
    ['user' => $intruderUser] = createPortalExamStudent();

    $result = StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $owner->id,
        'candidate_number' => 'CAND-SECRET',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    $this->actingAs($intruderUser)
        ->get(route('portal.exam-results.show', $result))
        ->assertNotFound();
});

test('successful lookup redirects to the saved session show page', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'LOOKUP01',
    ]);

    $this->mock(StudentExamResultAccessService::class, function ($mock) {
        $mock->shouldReceive('evaluate')->andReturn([
            'canViewResults' => true,
            'gate' => 'fees',
            'allowOnlineClearance' => false,
            'fees' => [
                'tuition' => 100,
                'autoCardFee' => 0,
                'partTimeLevy' => 0,
                'expectedTotal' => 100,
                'paidFromBank' => 100,
                'paidFromLedger' => 0,
                'paidTotal' => 100,
                'outstanding' => 0,
                'isFullyPaid' => true,
                'breakdown' => [],
                'hasStudentNumber' => true,
                'source' => 'enrolment',
            ],
            'clearance' => null,
            'idValidation' => ['required' => false, 'isValid' => true, 'needsCorrection' => false],
            'academicCalendarId' => null,
            'semesterId' => null,
        ]);
        $mock->shouldReceive('resolveEnrolmentContext')->andReturn(null);
    });

    ExaminationResult::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'LOOKUP01',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ], examinationIdentityForStudent($student)));

    $response = $this->actingAs($user)
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'LOOKUP01',
        ]);

    $saved = StudentExamResult::query()
        ->where('student_id', $student->id)
        ->where('candidate_number', 'LOOKUP01')
        ->first();

    expect($saved)->not->toBeNull();
    $response->assertRedirect(route('portal.exam-results.show', $saved));
});

test('portal lookup fails when profile names do not match examination record', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'NAME-MIS',
    ]);

    mockPortalExamResultAccess();

    ExaminationResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => null,
        'candidate_number' => 'NAME-MIS',
        'surname' => 'Different',
        'first_names' => 'Person',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ]);

    $this->actingAs($user)
        ->from(route('portal.exam-results'))
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'NAME-MIS',
        ])
        ->assertRedirect(route('portal.exam-results'))
        ->assertSessionHasErrors([
            'candidate_number' => __('trans.exam_results_name_mismatch'),
        ]);
});

test('portal lookup succeeds when examination first names include middle name', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'MIDNAME1',
    ]);

    mockPortalExamResultAccess();

    ExaminationResult::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'student_id' => null,
        'candidate_number' => 'MIDNAME1',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ], examinationIdentityForStudent($student)));

    $this->actingAs($user)
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'MIDNAME1',
        ])
        ->assertRedirect();

    expect(
        StudentExamResult::query()
            ->where('student_id', $student->id)
            ->where('candidate_number', 'MIDNAME1')
            ->exists()
    )->toBeTrue();
});

test('portal lookup backfills student_id on unlinked examination rows', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'BACKFILL',
    ]);

    mockPortalExamResultAccess();

    $examinationResult = ExaminationResult::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'student_id' => null,
        'candidate_number' => 'BACKFILL',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ], examinationIdentityForStudent($student)));

    $this->actingAs($user)
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'BACKFILL',
        ])
        ->assertRedirect();

    expect($examinationResult->fresh()->student_id)->toBe($student->id);
});

test('portal lookup fails when candidate is linked to another student', function () {
    ['user' => $user, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'LINKED1',
    ]);
    ['student' => $otherStudent] = createPortalExamStudent();

    mockPortalExamResultAccess();

    ExaminationResult::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'student_id' => $otherStudent->id,
        'candidate_number' => 'LINKED1',
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ], examinationIdentityForStudent($otherStudent)));

    $this->actingAs($user)
        ->from(route('portal.exam-results'))
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'LINKED1',
        ])
        ->assertRedirect(route('portal.exam-results'))
        ->assertSessionHasErrors([
            'candidate_number' => __('trans.exam_results_candidate_mismatch'),
        ]);
});

test('unmatched candidate lookup redirects back with candidate number errors', function () {
    ['user' => $user] = createPortalExamStudent([
        'student_number' => 'LOOKUPNO',
    ]);

    $this->mock(StudentExamResultAccessService::class, function ($mock) {
        $mock->shouldReceive('evaluate')->andReturn([
            'canViewResults' => true,
            'gate' => 'fees',
            'allowOnlineClearance' => false,
            'fees' => [
                'tuition' => 100,
                'autoCardFee' => 0,
                'partTimeLevy' => 0,
                'expectedTotal' => 100,
                'paidFromBank' => 100,
                'paidFromLedger' => 0,
                'paidTotal' => 100,
                'outstanding' => 0,
                'isFullyPaid' => true,
                'breakdown' => [],
                'hasStudentNumber' => true,
                'source' => 'enrolment',
            ],
            'clearance' => null,
            'idValidation' => ['required' => false, 'isValid' => true, 'needsCorrection' => false],
            'academicCalendarId' => null,
            'semesterId' => null,
        ]);
        $mock->shouldReceive('resolveEnrolmentContext')->andReturn(null);
    });

    $this->actingAs($user)
        ->from(route('portal.exam-results'))
        ->post(route('portal.exam-results.lookup'), [
            'candidate_number' => 'mudzedze',
        ])
        ->assertRedirect(route('portal.exam-results'))
        ->assertSessionHasErrors('candidate_number');
});

test('portal exam results unlock when a paid tuition ledger receipt covers the expected balance', function () {
    $studentApplication = createVerifiedStudentApplication('LOOKUPLEDG1');
    $student = $studentApplication->student;
    $user = $student->user;

    Permission::findOrCreate('viewOwnExamResults:students', 'web');
    $user->givePermissionTo('viewOwnExamResults:students');

    $student->update([
        'id_type_id' => IdType::query()->firstOrCreate(
            ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->label()],
            ['id' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()],
        )->id,
        'id_number' => null,
    ]);

    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::TUITION_FEE->slug()],
        [
            'name' => FeeTypeEnum::TUITION_FEE->name(),
            'description' => FeeTypeEnum::TUITION_FEE->description(),
            'position' => FeeTypeEnum::TUITION_FEE->position(),
        ],
    );

    FeeStructure::query()->updateOrCreate(
        [
            'tenant_id' => $studentApplication->tenant_id,
            'fee_type_id' => $feeType->id,
            'level_id' => $studentApplication->departmentLevel->level_id,
            'mode_of_study_id' => $studentApplication->mode_of_study_id,
        ],
        [
            'amount' => 100,
            'local_fca_amount' => 100,
        ],
    );

    Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $feeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 100,
        'system_reference' => 'ORD-LOOKUP-LEDGER',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    app(InstitutionFeatureService::class)
        ->setAllowOnlineClearance((int) $student->tenant_id, false);

    $this->actingAs($user)
        ->get(route('portal.exam-results'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResults')
            ->where('access.canViewResults', true)
            ->where('access.fees.paidFromLedger', 100)
            ->where('access.fees.outstanding', 0)
        );
});

test('student is cleared online via accounts when period end clearance is disabled', function () {
    ['user' => $user, 'student' => $student, 'tenant' => $tenant] = createPortalExamStudent([
        'student_number' => 'ACCCLR01',
    ]);

    $application = createVerifiedStudentApplication('PORT-ACC-'.strtoupper(Str::random(4)));
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $status->id,
    ]);

    StudentClearance::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'calendar_year' => 2026,
        'semester_id' => $semester->id,
        'accounts_cleared' => true,
    ]);

    app(InstitutionFeatureService::class)
        ->setAllowOnlineClearance((int) $tenant->id, false);

    $student->update([
        'id_type_id' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id(),
        'id_number' => null,
    ]);
    $student->refresh();

    $this->actingAs($user)
        ->get(route('portal.exam-results'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResults')
            ->where('access.allowOnlineClearance', false)
            ->where('access.gate', 'clearance')
            ->where('access.canViewResults', true)
            ->where('access.fees', null)
            ->has('access.clearance.sections', 1)
            ->where('access.clearance.sections.0.key', 'accounts')
        );
});

test('log book fee gap notice is only shown for ojet mode of study', function () {
    ['user' => $user, 'student' => $student] = createPortalExamStudent();

    $this->actingAs($user)
        ->get(route('portal.exam-results'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResults')
            ->where('logBookFeeGapNotice', null)
        );

    $application = createVerifiedStudentApplication('OJET-LOG-'.strtoupper(Str::random(4)));
    $ojet = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::OJET->value],
    );
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $ojet->id,
        'student_enrolment_status_id' => $status->id,
    ]);

    $this->actingAs($user)
        ->get(route('portal.exam-results'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/ExamResults')
            ->where('logBookFeeGapNotice', __('trans.log_book_fee_gap'))
        );
});
