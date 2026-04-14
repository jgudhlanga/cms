<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Acl\Role;
use App\Models\Enrolments\ClassList;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', config('app.timezone')));

    AcademicCalendar::query()->firstOrCreate(
        ['calendar_year' => '2025/2026'],
        [
            'opening_date' => '2026-01-01',
            'closing_date' => '2026-12-31',
        ],
    );

    foreach (['Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Completed'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('finalises verified students with matching payments in the date window', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentProgram = createVerifiedStudentProgram('STU001');
    $departmentStep = createEnrolledDepartmentStep($studentProgram);
    createBankCreditReceipt('STU001', '2026-01-10 09:00:00', 'TXN-BULK-001');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    $classList = ClassList::query()->where('student_program_id', $studentProgram->id)->first();
    $freshStudentProgram = $studentProgram->fresh();

    $enrolment = StudentEnrolment::query()->where('student_id', $studentProgram->student_id)->first();
    $activeStatusId = StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $semesterOneId = AcademicYearOption::query()->where('slug', 'semester-1')->value('id');
    $calendarId = AcademicCalendar::query()->where('calendar_year', '2025/2026')->value('id');

    expect($freshStudentProgram->program_status_id)->toBe(ClassListTypeEnum::VERIFIED->value)
        ->and($freshStudentProgram->department_application_step_id)->toBe($departmentStep->id)
        ->and($classList)->not->toBeNull()
        ->and($classList->type)->toBe(ClassListTypeEnum::FINAL)
        ->and($enrolment)->not->toBeNull()
        ->and($enrolment->student_enrolment_status_id)->toBe($activeStatusId)
        ->and($enrolment->academic_year_option_id)->toBe($semesterOneId)
        ->and($enrolment->academic_calendar_id)->toBe($calendarId);
});

it('is idempotent when the bulk finalise command runs more than once for the same paid student', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentProgram = createVerifiedStudentProgram('STU001B');
    createEnrolledDepartmentStep($studentProgram);
    createBankCreditReceipt('STU001B', '2026-01-10 09:00:00', 'TXN-BULK-001B');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    ClassList::query()->where('student_program_id', $studentProgram->id)->update([
        'type' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    expect(StudentEnrolment::query()->where('student_id', $studentProgram->student_id)->count())->toBe(1);
});

it('fails the command when no academic calendar can be resolved for a paid student', function () {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    AcademicCalendar::query()->delete();

    $studentProgram = createVerifiedStudentProgram('STU001C');
    createEnrolledDepartmentStep($studentProgram);
    createBankCreditReceipt('STU001C', '2026-01-10 09:00:00', 'TXN-BULK-001C');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertFailed();

    expect(StudentEnrolment::query()->where('student_id', $studentProgram->student_id)->exists())->toBeFalse();
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
