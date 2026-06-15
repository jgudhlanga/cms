<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Acl\Role;
use App\Models\Enrolments\ClassList;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Illuminate\Support\Str;

require_once __DIR__.'/MaintenanceControllerTest.php';

beforeEach(function (): void {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
    Role::findOrCreate(RoleEnum::LECTURER->name(), 'web');
});

function assignStudentRole(User $user): void
{
    $user->assignRole(RoleEnum::STUDENT->name());
}

function responseUserIds($response): array
{
    return collect($response->json('data'))
        ->pluck('id')
        ->map(static fn ($id) => (int) $id)
        ->all();
}

function createFeeType(FeeTypeEnum $feeTypeEnum): FeeType
{
    return FeeType::query()->firstOrCreate(
        ['slug' => $feeTypeEnum->slug()],
        [
            'name' => $feeTypeEnum->name(),
            'description' => $feeTypeEnum->description(),
            'position' => $feeTypeEnum->position(),
        ],
    );
}

function createReviewStudentProgram(string $studentNumber): StudentProgram
{
    $program = createVerifiedStudentProgram($studentNumber);
    $reviewStep = resolveDepartmentApplicationStep($program, WorkflowStepEnum::REVIEW);

    $program->update([
        'department_application_step_id' => $reviewStep->id,
    ]);

    ClassList::query()->where('student_program_id', $program->id)->delete();

    return $program->fresh();
}

function createActiveEnrolmentForProgram(StudentProgram $program): void
{
    $suffix = Str::lower(Str::random(6));

    $academicYearOption = AcademicYearOption::query()->create([
        'slug' => 'maint-users-'.$suffix,
        'name' => 'Semester '.$suffix,
        'description' => null,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $program->student_id,
        'student_program_id' => $program->id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $program->mode_of_study_id,
        'student_enrolment_status_id' => $status->id,
    ]);
}

function createPaidApplicationReceipt(User $user, StudentProgram $program): void
{
    $feeType = createFeeType(FeeTypeEnum::APPLICATION_FEE);

    Ledger::query()->create([
        'tenant_id' => $user->tenant_id,
        'ledgerable_type' => User::class,
        'ledgerable_id' => $user->id,
        'fee_type_id' => $feeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 50,
        'currency' => 'USD',
        'system_reference' => 'SYS-'.Str::upper(Str::random(8)),
        'student_program_id' => $program->id,
        'intake_period_id' => $program->intake_period_id,
    ]);
}

it('returns unauthorized for guests on non-enrolled student users endpoint', function (): void {
    $this->getJson(route('maintenance.non-enrolled-student-users'))
        ->assertUnauthorized();
});

it('forbids users without root manage from non-enrolled student users endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->getJson(route('maintenance.non-enrolled-student-users'))
        ->assertForbidden();
});

it('includes student role users without a student profile', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $studentUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'No',
        'last_name' => 'Profile',
    ]);
    assignStudentRole($studentUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->toContain((int) $studentUser->id);
});

it('includes student role users with a review programme and no active enrolment or fees', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $program = createReviewStudentProgram('NEU-REV-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;
    $studentUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($studentUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->toContain((int) $studentUser->id);
});

it('includes student role users with a verified class list and no active enrolment or fees', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $program = createVerifiedStudentProgram('NEU-VER-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;
    $studentUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($studentUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->toContain((int) $studentUser->id);

    $matched = collect($response->json('data'))->firstWhere('id', $studentUser->id);
    expect($matched['attributes']['applicationStatusSummary'])->toBe('Verified');
});

it('excludes student role users with an active enrolment', function (): void {
    actingAsRootMaintenanceUser();

    $program = createVerifiedStudentProgram('NEU-ACT-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;
    assignStudentRole($studentUser);
    createActiveEnrolmentForProgram($program);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->not->toContain((int) $studentUser->id);
});

it('includes student role users with a paid application receipt when otherwise eligible', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $program = createReviewStudentProgram('NEU-FEE-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;
    $studentUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($studentUser);
    createPaidApplicationReceipt($studentUser, $program);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->toContain((int) $studentUser->id);
});

it('filters non-enrolled student users by application status no profile', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $noProfileUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Filter',
        'last_name' => 'NoProfile',
    ]);
    assignStudentRole($noProfileUser);

    $program = createVerifiedStudentProgram('NEU-FIL-'.strtoupper(Str::random(4)));
    $verifiedUser = $program->student->user;
    $verifiedUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($verifiedUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users', [
        'application_status' => 'no_profile',
    ]));
    $response->assertOk();

    $ids = responseUserIds($response);

    expect($ids)->toContain((int) $noProfileUser->id)
        ->and($ids)->not->toContain((int) $verifiedUser->id);
});

it('filters non-enrolled student users by application status verified', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $noProfileUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Filter',
        'last_name' => 'VerifiedExclude',
    ]);
    assignStudentRole($noProfileUser);

    $program = createVerifiedStudentProgram('NEU-FVR-'.strtoupper(Str::random(4)));
    $verifiedUser = $program->student->user;
    $verifiedUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($verifiedUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users', [
        'application_status' => 'verified',
    ]));
    $response->assertOk();

    $ids = responseUserIds($response);

    expect($ids)->toContain((int) $verifiedUser->id)
        ->and($ids)->not->toContain((int) $noProfileUser->id);
});

it('filters non-enrolled student users by application status review', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $noProfileUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Filter',
        'last_name' => 'ReviewExclude',
    ]);
    assignStudentRole($noProfileUser);

    $program = createReviewStudentProgram('NEU-FRV-'.strtoupper(Str::random(4)));
    $reviewUser = $program->student->user;
    $reviewUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($reviewUser);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users', [
        'application_status' => 'review',
    ]));
    $response->assertOk();

    $ids = responseUserIds($response);

    expect($ids)->toContain((int) $reviewUser->id)
        ->and($ids)->not->toContain((int) $noProfileUser->id);
});

it('excludes non-student role users even when profile criteria match', function (): void {
    actingAsRootMaintenanceUser();

    $program = createReviewStudentProgram('NEU-LEC-'.strtoupper(Str::random(4)));
    $lecturerUser = $program->student->user;
    $lecturerUser->syncRoles([RoleEnum::LECTURER->name()]);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    expect(responseUserIds($response))->not->toContain((int) $lecturerUser->id);
});

it('filters non-enrolled student users by search term', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $included = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Searchable',
        'last_name' => 'Candidate',
        'email' => 'searchable.candidate@example.com',
    ]);
    assignStudentRole($included);

    $excluded = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Other',
        'last_name' => 'Person',
        'email' => 'other.person@example.com',
    ]);
    assignStudentRole($excluded);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users', ['search' => 'Searchable']));
    $response->assertOk();

    $ids = responseUserIds($response);

    expect($ids)->toContain((int) $included->id)
        ->and($ids)->not->toContain((int) $excluded->id);
});

it('includes student role users with a profile and no programmes', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $studentUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Empty',
        'last_name' => 'Profile',
    ]);
    assignStudentRole($studentUser);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID']);

    Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'student_number' => 'NEU-EMPTY-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    $matched = collect($response->json('data'))->firstWhere('id', $studentUser->id);
    expect($matched)->not->toBeNull()
        ->and($matched['attributes']['applicationStatusSummary'])->toBe('No programmes');
});
