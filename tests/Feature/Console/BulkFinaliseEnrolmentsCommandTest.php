<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\Acl\Role;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function createVerifiedStudentProgram(string $studentNumber): StudentProgram
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ict',
        'description' => 'Department for bulk finalise command tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create(['name' => 'Level 1']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $title = Title::query()->create(['name' => 'Mr']);
    $gender = Gender::query()->create(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'Single']);
    $idType = IdType::query()->create(['name' => 'National ID']);
    $studentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Test',
        'middle_name' => 'Bulk',
        'last_name' => 'Student',
    ]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-000000A00',
        'student_number' => $studentNumber,
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
        'application_tracking_number' => 'APP-'.strtoupper(str()->random(8)),
        'program_status_id' => ClassListTypeEnum::VERIFIED->value,
    ]);

    ClassList::query()->create([
        'tenant_id' => $tenant->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::VERIFIED->value,
        'attributes' => [],
    ]);

    return $studentProgram;
}

function createBankCreditReceipt(string $studentNumber, string $transactionDate, string $transactionId): void
{
    ZBBankStatement::query()->create([
        'tran_number_asc' => 'T-ASC-'.$transactionId,
        'tran_number_desc' => 'T-DESC-'.$transactionId,
        'transaction_id' => $transactionId,
        'transaction_sr_id' => 'TSR-'.$transactionId,
        'transaction_date' => $transactionDate,
        'debit_credit_flag' => 'C',
        'narration' => "Fees payment {$studentNumber}",
    ]);
}

function createEnrolledDepartmentStep(StudentProgram $studentProgram): DepartmentApplicationStep
{
    $step = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::ENROLLED->slug()],
        [
            'name' => WorkflowStepEnum::ENROLLED->name(),
            'description' => WorkflowStepEnum::ENROLLED->description(),
            'position' => WorkflowStepEnum::ENROLLED->position(),
        ]
    );

    return DepartmentApplicationStep::query()->create([
        'tenant_id' => $studentProgram->tenant_id,
        'institution_department_id' => $studentProgram->institution_department_id,
        'workflow_step_id' => $step->id,
        'position' => $step->position,
    ]);
}

it('finalises verified students with matching payments in the date window', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentProgram = createVerifiedStudentProgram('STU001');
    $departmentStep = createEnrolledDepartmentStep($studentProgram);
    createBankCreditReceipt('STU001', '2026-01-10 09:00:00', 'TXN-BULK-001');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    $classList = ClassList::query()->where('student_program_id', $studentProgram->id)->first();
    $freshStudentProgram = $studentProgram->fresh();

    expect($freshStudentProgram->program_status_id)->toBe(ClassListTypeEnum::FINAL->value)
        ->and($freshStudentProgram->department_application_step_id)->toBe($departmentStep->id)
        ->and($classList)->not->toBeNull()
        ->and($classList->type)->toBe(ClassListTypeEnum::FINAL);
});

it('keeps verified students unchanged when no matching payment exists', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentProgram = createVerifiedStudentProgram('STU002');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    expect($studentProgram->fresh()->program_status_id)->toBe(ClassListTypeEnum::VERIFIED->value);
});

it('applies explicit start-date and end-date options when filtering payments', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentProgram = createVerifiedStudentProgram('STU003');
    createBankCreditReceipt('STU003', '2026-01-03 09:00:00', 'TXN-BULK-002');

    $this->artisan('enrolments:bulk-finalise-enrolments-command', [
        '--start-date' => '2026-01-04',
        '--end-date' => '2026-01-31',
    ])->assertSuccessful();

    expect($studentProgram->fresh()->program_status_id)->toBe(ClassListTypeEnum::VERIFIED->value);
});

it('writes a CSV failure report to local storage', function () {
    Storage::fake('local');
    Mail::fake();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    createVerifiedStudentProgram('STU004');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    $files = Storage::disk('local')->allFiles('reports/enrolments');
    expect($files)->not->toBeEmpty();

    $reportPath = collect($files)->first(function (string $path): bool {
        return Str::startsWith($path, 'reports/enrolments/bulk-finalise-failures-') && Str::endsWith($path, '.csv');
    });

    expect($reportPath)->not->toBeNull();

    $csv = Storage::disk('local')->get($reportPath);
    expect($csv)->toContain('Test Bulk Student')
        ->and($csv)->toContain('63-000000A00')
        ->and($csv)->toContain('STU004');

    Mail::assertNothingSent();
});

it('emails super users in production with the failure report', function () {
    Storage::fake('local');
    Mail::fake();
    $this->app->detectEnvironment(fn () => 'production');
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $superUserRoleName = RoleEnum::SUPER_USER->name();
    Role::query()->firstOrCreate(
        ['name' => $superUserRoleName],
        [
            'slug' => Str::slug($superUserRoleName),
            'guard_name' => 'web',
        ]
    );

    $recipient = User::factory()->create(['email' => 'super.user@example.test']);
    $recipient->assignRole($superUserRoleName);

    createVerifiedStudentProgram('STU005');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    Mail::assertSent(BulkFinaliseEnrolmentsReportMail::class, function (BulkFinaliseEnrolmentsReportMail $mail) use ($recipient): bool {
        $attachments = $mail->attachments();

        return $mail->hasTo($recipient->email) && count($attachments) > 0;
    });
});
