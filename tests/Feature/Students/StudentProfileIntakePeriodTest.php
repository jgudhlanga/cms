<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Rbac\Role;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use App\Support\Rbac\PermissionRegistry;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

function createIntakePeriodManager(Student $student): User
{
    $user = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $user->givePermissionTo(['view:students', 'viewAny:students', 'change-intake-period:students']);

    return $user;
}

function createAlternateIntakePeriod(Student $student, string $suffix = ''): IntakePeriod
{
    return IntakePeriod::query()->create([
        'tenant_id' => $student->tenant_id,
        'name' => 'Alternate Intake '.($suffix !== '' ? $suffix : Str::random(5)),
        'start_date' => now()->addMonths(2)->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => 'open',
        'is_continuous' => false,
    ]);
}

it('registers the change intake period permission', function (): void {
    expect(PermissionRegistry::allValues())
        ->toContain('change-intake-period:students');
});

it('grants the change intake period permission to super user only', function (): void {
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();

    $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->firstOrFail();

    expect($superUser->permissions->pluck('name')->all())
        ->toContain('change-intake-period:students');

    $otherRoleNames = [
        RoleEnum::PRINCIPAL->name(),
        RoleEnum::REGISTRAR->name(),
        RoleEnum::REGISTRY_OFFICER->name(),
        RoleEnum::HEAD_OF_DEPARTMENT->name(),
        RoleEnum::IT_SUPPORT_TECHNICIAN->name(),
    ];

    foreach ($otherRoleNames as $roleName) {
        $permissions = Role::query()->where('name', $roleName)->firstOrFail()->permissions->pluck('name')->all();

        expect($permissions)->not->toContain('change-intake-period:students');
    }
});

it('updates the header application intake and records the reason on the activity trail', function (): void {
    $application = createVerifiedStudentApplication('INT-A-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $newIntake = createAlternateIntakePeriod($student, 'A');
    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Registry corrected the intake period on the application.',
        ])
        ->assertRedirect();

    expect($application->fresh()->intake_period_id)->toBe($newIntake->id);

    $activity = Activity::query()
        ->where('subject_type', $student->getMorphClass())
        ->where('subject_id', $student->id)
        ->where('event', 'intake-period-changed')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['new_intake_period_id'])->toBe($newIntake->id)
        ->and($activity->properties['student_application_id'])->toBe($application->id)
        ->and($activity->properties['reason'])->toBe('Registry corrected the intake period on the application.')
        ->and($activity->causer_id)->toBe($manager->id);
});

it('leaves sibling applications on the previous intake unchanged', function (): void {
    $application = createVerifiedStudentApplication('INT-B-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $oldIntakeId = (int) $application->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'B');

    // Created after the original so latestOfMany treats this as the header application.
    $headerApplication = StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'intake_period_id' => $oldIntakeId,
        'mode_of_study_id' => $application->mode_of_study_id,
        'workflow_step_id' => $application->workflow_step_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Only the latest application should move intake.',
        ])
        ->assertRedirect();

    expect($headerApplication->fresh()->intake_period_id)->toBe($newIntake->id)
        ->and($application->fresh()->intake_period_id)->toBe($oldIntakeId);
});

it('moves application-linked fees and ledgers to the new intake', function (): void {
    $application = createVerifiedStudentApplication('INT-C-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $oldIntakeId = (int) $application->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'C');
    $level = Level::query()->findOrFail($application->departmentLevel->level_id);
    $feeType = FeeType::query()->firstOrCreate(['name' => 'Application Fee']);

    $fee = ApplicationFee::query()->create([
        'tenant_id' => $student->tenant_id,
        'user_id' => $student->user_id,
        'intake_period_id' => $oldIntakeId,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
        'student_application_id' => $application->id,
    ]);

    $ledger = Ledger::query()->create([
        'tenant_id' => $student->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $application->id,
        'student_application_id' => $application->id,
        'fee_type_id' => $feeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 20,
        'currency' => 'USD',
        'system_reference' => 'ORD-INT-C-001',
        'intake_period_id' => $oldIntakeId,
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Moving fees and ledgers with the application intake.',
        ])
        ->assertRedirect();

    expect($fee->fresh()->intake_period_id)->toBe($newIntake->id)
        ->and($ledger->fresh()->intake_period_id)->toBe($newIntake->id);
});

it('moves the pastel register row only when it is tagged with the old intake', function (): void {
    $application = createVerifiedStudentApplication('INT-D-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $oldIntakeId = (int) $application->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'D');
    $unrelatedIntake = createAlternateIntakePeriod($student, 'D-OTHER');

    $matching = PastelLinkedStudent::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'student_number' => (string) $student->student_number,
        'intake_period_id' => $oldIntakeId,
        'linked_at' => now(),
    ]);

    $otherStudent = createVerifiedStudentApplication('INT-D2-'.strtoupper(Str::random(4)))->student;
    $unrelated = PastelLinkedStudent::query()->create([
        'tenant_id' => $otherStudent->tenant_id,
        'student_id' => $otherStudent->id,
        'student_number' => (string) $otherStudent->student_number,
        'intake_period_id' => $unrelatedIntake->id,
        'linked_at' => now(),
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Pastel row should follow the previous intake tag.',
        ])
        ->assertRedirect();

    expect($matching->fresh()->intake_period_id)->toBe($newIntake->id)
        ->and($unrelated->fresh()->intake_period_id)->toBe($unrelatedIntake->id);
});

it('leaves a shared fee on the old intake when a sibling application still uses it', function (): void {
    $sibling = createVerifiedStudentApplication('INT-E-'.strtoupper(Str::random(4)));
    $student = $sibling->student;
    $oldIntakeId = (int) $sibling->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'E');
    $level = Level::query()->findOrFail($sibling->departmentLevel->level_id);

    $headerApplication = StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'institution_department_id' => $sibling->institution_department_id,
        'department_level_id' => $sibling->department_level_id,
        'department_course_id' => $sibling->department_course_id,
        'intake_period_id' => $oldIntakeId,
        'mode_of_study_id' => $sibling->mode_of_study_id,
        'workflow_step_id' => $sibling->workflow_step_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
    ]);

    $sharedFee = ApplicationFee::query()->create([
        'tenant_id' => $student->tenant_id,
        'user_id' => $student->user_id,
        'intake_period_id' => $oldIntakeId,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
        'student_application_id' => null,
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Shared fee must stay with the sibling application intake.',
        ])
        ->assertRedirect();

    expect($headerApplication->fresh()->intake_period_id)->toBe($newIntake->id)
        ->and($sibling->fresh()->intake_period_id)->toBe($oldIntakeId)
        ->and($sharedFee->fresh()->intake_period_id)->toBe($oldIntakeId);
});

it('rejects moving when a fee already exists on the target intake for the user', function (): void {
    $application = createVerifiedStudentApplication('INT-F-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $oldIntakeId = (int) $application->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'F');
    $level = Level::query()->findOrFail($application->departmentLevel->level_id);

    ApplicationFee::query()->create([
        'tenant_id' => $student->tenant_id,
        'user_id' => $student->user_id,
        'intake_period_id' => $oldIntakeId,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
        'student_application_id' => $application->id,
    ]);

    ApplicationFee::query()->create([
        'tenant_id' => $student->tenant_id,
        'user_id' => $student->user_id,
        'intake_period_id' => $newIntake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
        'student_application_id' => null,
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Cannot collide with an existing fee on the target intake.',
        ])
        ->assertSessionHasErrors('intake_period_id');

    expect($application->fresh()->intake_period_id)->toBe($oldIntakeId);
});

it('requires a reason when changing the intake period', function (): void {
    $application = createVerifiedStudentApplication('INT-G-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $newIntake = createAlternateIntakePeriod($student, 'G');
    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'too short',
        ])
        ->assertSessionHasErrors('reason');
});

it('forbids changing the intake period without the permission', function (): void {
    $application = createVerifiedStudentApplication('INT-H-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $newIntake = createAlternateIntakePeriod($student, 'H');

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students', 'update:students']);

    $this->actingAs($viewer)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'No permission to change this intake period.',
        ])
        ->assertForbidden();
});

it('updates the enrolment linked application when the student is enrolled', function (): void {
    $application = createVerifiedStudentApplication('INT-I-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $oldIntakeId = (int) $application->intake_period_id;
    $newIntake = createAlternateIntakePeriod($student, 'I');

    $unknownStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Unknown'],
        ['description' => 'Exam results not recorded yet.'],
    );

    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $unknownStatus->id,
    ]);

    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->patch(route('students.intake-period.update', $student), [
            'intake_period_id' => $newIntake->id,
            'reason' => 'Enrolled student intake follows the linked application.',
        ])
        ->assertRedirect();

    expect($application->fresh()->intake_period_id)->toBe($newIntake->id)
        ->and($oldIntakeId)->not->toBe($newIntake->id);
});

it('exposes intake period options and id on the student profile page', function (): void {
    $application = createVerifiedStudentApplication('INT-J-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $manager = createIntakePeriodManager($student);

    $this->actingAs($manager)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->has('studentIntakePeriodOptions')
            ->where('student.attributes.intakePeriodId', $application->intake_period_id)
            ->where('student.attributes.intakePeriod', $application->intakePeriod?->name));
});
