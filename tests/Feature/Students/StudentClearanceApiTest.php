<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\StudentClearanceSection;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\FeeStructure;
use App\Models\Shared\FeeType;
use App\Models\Shared\IdType;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentClearance;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use App\Services\Institution\InstitutionFeatureService;
use App\Services\Students\StudentExamResultAccessService;
use App\Services\Students\StudentFeeClearanceService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (['Term 1', 'Term 2', 'Semester 1', 'Semester 2', 'ABMA 1'] as $name) {
        Semester::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'completed'],
        ['name' => 'Completed', 'description' => 'Test'],
    );
});

function createClearanceStaffUser(int $tenantId, array $permissions): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

test('clearance show filters period options by level calendar type and defaults year from active enrolment', function () {
    $application = createVerifiedStudentApplication('CLR-TERM-'.strtoupper(Str::random(4)));
    $application->departmentLevel->level->update(['calendar_type' => AcademicCalendarTypeEnum::TERM->value]);

    $termCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);

    $oldCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-04-30',
    ]);

    $termOneId = (int) Semester::query()->where('slug', 'term-1')->value('id');
    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $completedStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $termOneId,
        'academic_calendar_id' => $oldCalendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $completedStatusId,
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $termOneId,
        'academic_calendar_id' => $termCalendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $staff = createClearanceStaffUser((int) $application->tenant_id, ['student-clearance:accounts']);
    Sanctum::actingAs($staff);

    $response = $this->getJson(route('v1.students.clearance.show', $application->student_id));

    $response->assertOk()
        ->assertJsonPath('data.calendarType', 'term')
        ->assertJsonPath('data.defaults.calendarYear', 2026)
        ->assertJsonPath('data.defaults.semesterId', $termOneId)
        ->assertJsonMissingPath('data.options.academicCalendars');

    $semesterIds = collect($response->json('data.options.semesters'))->pluck('id')->all();

    expect($semesterIds)->toContain($termOneId)
        ->and($semesterIds)->not->toContain($semesterOneId);
});

test('clearance show returns identity based on id type', function () {
    $application = createVerifiedStudentApplication('CLR-ID-'.strtoupper(Str::random(4)));
    $zimIdType = IdType::query()->find(IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id())
        ?? IdType::query()->firstOrCreate(
            ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->label()],
            ['id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()],
        );

    $application->student->update([
        'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
        'id_number' => '63-1234567N63',
        'passport_number' => null,
    ]);

    // Ensure enum id row exists for isZimbabwean() check.
    if ((int) $zimIdType->id !== IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()) {
        IdType::query()->updateOrCreate(
            ['id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()],
            ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->label()],
        );
        $application->student->update(['id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()]);
    }

    $staff = createClearanceStaffUser((int) $application->tenant_id, ['student-clearance:library']);
    Sanctum::actingAs($staff);

    $this->getJson(route('v1.students.clearance.show', $application->student_id))
        ->assertOk()
        ->assertJsonPath('data.identity.isZimbabwean', true)
        ->assertJsonPath('data.identity.idNumber', '63-1234567N63');
});

test('clearance batch update stores calendar year not academic calendar id', function () {
    $application = createVerifiedStudentApplication('CLR-SAVE-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $staff = createClearanceStaffUser((int) $application->tenant_id, [
        'student-clearance:accounts',
        'student-clearance:library',
    ]);
    Sanctum::actingAs($staff);

    app(InstitutionFeatureService::class)->setAllowOnlineClearance((int) $application->tenant_id, true);

    $this->putJson(route('v1.students.clearance.update', $application->student_id), [
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'sections' => [
            ['section' => StudentClearanceSection::Accounts->value, 'cleared' => true, 'notes' => null],
            ['section' => StudentClearanceSection::Library->value, 'cleared' => false, 'notes' => 'Books outstanding'],
        ],
    ])->assertOk()
        ->assertJsonPath('data.isFullyCleared', false)
        ->assertJsonPath('data.calendarYear', 2026);

    $clearance = StudentClearance::query()
        ->where('student_id', $application->student_id)
        ->where('calendar_year', 2026)
        ->where('semester_id', $semesterId)
        ->first();

    expect($clearance)->not->toBeNull()
        ->and($clearance->accounts_cleared)->toBeTrue()
        ->and($clearance->library_cleared)->toBeFalse()
        ->and($clearance->library_notes)->toBe('Books outstanding')
        ->and($clearance->security_cleared)->toBeFalse();
});

test('clearance batch update forbids sections the user cannot edit', function () {
    $application = createVerifiedStudentApplication('CLR-FORBIDDEN-'.strtoupper(Str::random(4)));
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');

    $staff = createClearanceStaffUser((int) $application->tenant_id, ['student-clearance:accounts']);
    Sanctum::actingAs($staff);

    $this->putJson(route('v1.students.clearance.update', $application->student_id), [
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'sections' => [
            ['section' => StudentClearanceSection::Accounts->value, 'cleared' => true, 'notes' => null],
            ['section' => StudentClearanceSection::Security->value, 'cleared' => true, 'notes' => null],
        ],
    ])->assertForbidden();
});

test('resolveEnrolmentContext prefers active enrolment over newer completed enrolment', function () {
    $application = createVerifiedStudentApplication('CLR-CTX-'.strtoupper(Str::random(4)));

    $olderActiveCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $newerCompletedCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);

    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $completedStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');

    $activeEnrolment = StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $olderActiveCalendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $newerCompletedCalendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $completedStatusId,
    ]);

    $resolved = app(StudentExamResultAccessService::class)
        ->resolveEnrolmentContext($application->student->fresh());

    expect($resolved?->id)->toBe($activeEnrolment->id);
});

test('parseCalendarYear prefers the later year from slash ranges', function () {
    $service = app(StudentExamResultAccessService::class);

    expect($service->parseCalendarYear('2025/2026'))->toBe(2026)
        ->and($service->parseCalendarYear('2026'))->toBe(2026)
        ->and($service->parseCalendarYear(2027))->toBe(2027)
        ->and($service->parseCalendarYear('invalid'))->toBeNull();
});

test('clearanceStatus looks up by calendar year and semester', function () {
    $application = createVerifiedStudentApplication('CLR-GATE-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentClearance::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'accounts_cleared' => true,
        'library_cleared' => true,
        'security_cleared' => true,
        'hostel_cleared' => true,
        'department_cleared' => true,
    ]);

    $status = app(StudentExamResultAccessService::class)
        ->clearanceStatus($application->student->fresh());

    expect($status['isFullyCleared'])->toBeTrue()
        ->and($status['calendarYear'])->toBe(2026)
        ->and($status['semesterId'])->toBe($semesterId);
});

test('clearance show returns only accounts when period end clearance is disabled', function () {
    $application = createVerifiedStudentApplication('CLR-ACC-ONLY-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentClearance::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'accounts_cleared' => true,
        'library_cleared' => true,
    ]);

    $staff = createClearanceStaffUser((int) $application->tenant_id, ['student-clearance:accounts']);
    Sanctum::actingAs($staff);

    $this->getJson(route('v1.students.clearance.show', $application->student_id))
        ->assertOk()
        ->assertJsonPath('data.allowOnlineClearance', false)
        ->assertJsonCount(1, 'data.clearance.sections')
        ->assertJsonPath('data.clearance.sections.0.key', StudentClearanceSection::Accounts->value);
});

test('clearance update rejects non accounts sections when period end clearance is disabled', function () {
    $application = createVerifiedStudentApplication('CLR-REJECT-'.strtoupper(Str::random(4)));
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');

    $staff = createClearanceStaffUser((int) $application->tenant_id, [
        'student-clearance:accounts',
        'student-clearance:library',
    ]);
    Sanctum::actingAs($staff);

    $this->putJson(route('v1.students.clearance.update', $application->student_id), [
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'sections' => [
            ['section' => StudentClearanceSection::Library->value, 'cleared' => true, 'notes' => null],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['sections']);
});

test('evaluate allows exam results when accounts cleared while period end clearance is disabled', function () {
    $application = createVerifiedStudentApplication('CLR-EVAL-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentClearance::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2026,
        'semester_id' => $semesterId,
        'accounts_cleared' => true,
        'library_cleared' => true,
    ]);

    $access = app(StudentExamResultAccessService::class)->evaluate($application->student->fresh());

    expect($access['allowOnlineClearance'])->toBeFalse()
        ->and($access['gate'])->toBe('clearance')
        ->and($access['canViewResults'])->toBeTrue()
        ->and($access['fees'])->toBeNull()
        ->and($access['clearance']['accountsCleared'])->toBeTrue()
        ->and($access['clearance']['sections'])->toHaveCount(1)
        ->and($access['clearance']['sections'][0]['key'])->toBe(StudentClearanceSection::Accounts->value);
});

test('evaluate uses fee gate when accounts not cleared and period end clearance is disabled', function () {
    $application = createVerifiedStudentApplication('CLR-FEES-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $access = app(StudentExamResultAccessService::class)->evaluate($application->student->fresh());

    expect($access['allowOnlineClearance'])->toBeFalse()
        ->and($access['gate'])->toBe('fees')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['clearance']['accountsCleared'])->toBeFalse()
        ->and($access['fees'])->not->toBeNull()
        ->and($access['clearance']['sections'])->toHaveCount(1)
        ->and($access['clearance']['sections'][0]['key'])->toBe(StudentClearanceSection::Accounts->value);
});

test('evaluate unlocks exam results for current-year apprentices without fee payment', function () {
    $application = createVerifiedStudentApplication('CLR-APP-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentApprentice::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2026,
        'employer' => 'Test Employer',
        'apprentice_number' => 'APP-2026-1',
    ]);

    $student = $application->student->fresh();
    $access = app(StudentExamResultAccessService::class)->evaluate($student);
    $fees = app(StudentFeeClearanceService::class)->evaluate($student);

    expect($access['allowOnlineClearance'])->toBeFalse()
        ->and($access['gate'])->toBe('apprentice')
        ->and($access['canViewResults'])->toBeTrue()
        ->and($access['fees'])->toBeNull()
        ->and($fees['isFullyPaid'])->toBeFalse();
});

test('evaluate keeps fee gate when apprentice record is for a different year', function () {
    $application = createVerifiedStudentApplication('CLR-APP-YR-'.strtoupper(Str::random(4)));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentApprentice::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2025,
        'employer' => 'Prior Employer',
        'apprentice_number' => 'APP-2025-1',
    ]);

    $access = app(StudentExamResultAccessService::class)->evaluate($application->student->fresh());

    expect($access['gate'])->toBe('fees')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['fees'])->not->toBeNull();
});

test('evaluate does not use apprentice fee exemption when period end clearance is enabled', function () {
    $application = createVerifiedStudentApplication('CLR-APP-CLR-'.strtoupper(Str::random(4)));

    app(InstitutionFeatureService::class)->setAllowOnlineClearance((int) $application->tenant_id, true);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    StudentApprentice::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'calendar_year' => 2026,
        'employer' => 'Test Employer',
        'apprentice_number' => 'APP-2026-2',
    ]);

    $access = app(StudentExamResultAccessService::class)->evaluate($application->student->fresh());

    expect($access['allowOnlineClearance'])->toBeTrue()
        ->and($access['gate'])->toBe('clearance')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['fees'])->toBeNull();
});

test('evaluate uses not_enrolled gate when student has no enrolment', function () {
    $application = createVerifiedStudentApplication('CLR-NE-'.strtoupper(Str::random(4)));

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
            'tenant_id' => $application->tenant_id,
            'fee_type_id' => $feeType->id,
            'level_id' => $application->departmentLevel->level_id,
            'mode_of_study_id' => $application->mode_of_study_id,
        ],
        [
            'amount' => 425,
            'local_fca_amount' => 425,
        ],
    );

    $student = $application->student->fresh();
    $access = app(StudentExamResultAccessService::class)->evaluate($student);
    $fees = app(StudentFeeClearanceService::class)->evaluate($student);

    expect($access['gate'])->toBe('not_enrolled')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['fees'])->toBeNull()
        ->and($fees['isEnrolled'])->toBeFalse()
        ->and($fees['expectedTotal'])->toBe(0.0)
        ->and($fees['source'])->toBeNull();
});

test('evaluate uses non_hexco gate for abma and term calendar levels while fees still assess', function (AcademicCalendarTypeEnum $calendarType) {
    $application = createVerifiedStudentApplication('CLR-NH-'.strtoupper(Str::random(4)));
    $application->departmentLevel->level->update(['calendar_type' => $calendarType->value]);

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
            'tenant_id' => $application->tenant_id,
            'fee_type_id' => $feeType->id,
            'level_id' => $application->departmentLevel->level_id,
            'mode_of_study_id' => $application->mode_of_study_id,
        ],
        [
            'amount' => 200,
            'local_fca_amount' => 200,
        ],
    );

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => $calendarType,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $student = $application->student->fresh();
    $access = app(StudentExamResultAccessService::class)->evaluate($student);
    $fees = app(StudentFeeClearanceService::class)->evaluate($student);

    expect($access['gate'])->toBe('non_hexco')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['fees'])->toBeNull()
        ->and($access['calendarType'])->toBe($calendarType->value)
        ->and($fees['isEnrolled'])->toBeTrue()
        ->and($fees['expectedTotal'])->toBe(200.0)
        ->and($fees['source'])->toBe('enrolment');
})->with([
    AcademicCalendarTypeEnum::TERM,
    AcademicCalendarTypeEnum::ABMA,
]);

test('evaluate still uses fee gate for semester calendar levels', function () {
    $application = createVerifiedStudentApplication('CLR-SEM-'.strtoupper(Str::random(4)));
    $application->departmentLevel->level->update(['calendar_type' => AcademicCalendarTypeEnum::SEMESTER->value]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $access = app(StudentExamResultAccessService::class)->evaluate($application->student->fresh());

    expect($access['gate'])->toBe('fees')
        ->and($access['canViewResults'])->toBeFalse()
        ->and($access['fees'])->not->toBeNull()
        ->and($access['calendarType'])->toBe(AcademicCalendarTypeEnum::SEMESTER->value);
});
