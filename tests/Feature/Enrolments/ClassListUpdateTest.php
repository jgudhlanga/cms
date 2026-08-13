<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Rbac\Permission;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', config('app.timezone')));

    AcademicCalendar::query()->firstOrCreate(
        [
            'calendar_year' => '2025/2026',
            'type' => 'semester',
        ],
        [
            'opening_date' => '2026-01-01',
            'closing_date' => '2026-12-31',
        ],
    );

    foreach (['Semester 1', 'Semester 2'] as $name) {
        Semester::query()->firstOrCreate(
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

function actingAsClassListStaff(?int $tenantId = null): User
{
    $user = User::factory()->create(array_filter([
        'tenant_id' => $tenantId,
    ]));
    Permission::findOrCreate('manage-final:class-lists', 'web');
    Permission::findOrCreate('verify:class-lists', 'web');
    $user->givePermissionTo(['manage-final:class-lists', 'verify:class-lists']);

    return $user;
}

function confirmationPayload(array $overrides = []): array
{
    return array_merge([
        'identity_confirmed' => false,
        'disability_confirmed' => false,
        'names_confirmed' => false,
        'o_level_confirmed' => false,
        'previous_level_confirmed' => false,
        'read_write_confirmed' => false,
        'application_fee_confirmed' => false,
        'proof_of_payment_confirmed' => true,
        'passport_photos_confirmed' => true,
        'original_birth_certificate_confirmed' => true,
        'original_national_identity_confirmed' => true,
        'original_education_certificates_confirmed' => true,
        'type' => 'verified',
    ], $overrides);
}

function verificationPayload(array $overrides = []): array
{
    return array_merge([
        'identity_confirmed' => true,
        'disability_confirmed' => true,
        'names_confirmed' => true,
        'o_level_confirmed' => true,
        'previous_level_confirmed' => false,
        'read_write_confirmed' => false,
        'application_fee_confirmed' => false,
        'proof_of_payment_confirmed' => false,
        'passport_photos_confirmed' => false,
        'original_birth_certificate_confirmed' => false,
        'original_national_identity_confirmed' => false,
        'original_education_certificates_confirmed' => false,
        'type' => 'provisional',
    ], $overrides);
}

it('elevates a verified student to final class even when verification flags are false', function () {
    $studentApplication = createVerifiedStudentApplication('CL-CONFIRM-001');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);
    $enrolledStep = createEnrolledDepartmentStep($studentApplication);

    ClassList::query()->where('student_application_id', $studentApplication->id)->update([
        'attributes' => [
            'identity_confirmed' => false,
            'disability_confirmed' => false,
            'names_confirmed' => false,
        ],
    ]);

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), confirmationPayload())
        ->assertRedirect(route('enrolments.confirm', $studentApplication))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    $classList = ClassList::query()->where('student_application_id', $studentApplication->id)->first();
    $enrolment = StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->first();

    expect($classList?->type)->toBe(ClassListTypeEnum::FINAL)
        ->and($studentApplication->fresh()->department_application_step_id)->toBe($enrolledStep->id)
        ->and($enrolment)->not->toBeNull()
        ->and($classList?->attributes['passport_photos_confirmed'])->toBeTrue()
        ->and($classList?->attributes['original_birth_certificate_confirmed'])->toBeTrue()
        ->and($classList?->attributes['original_national_identity_confirmed'])->toBeTrue()
        ->and($classList?->attributes['original_education_certificates_confirmed'])->toBeTrue();
});

it('rejects verified confirmation when document flags are missing', function () {
    $studentApplication = createVerifiedStudentApplication('CL-CONFIRM-002');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);
    $acceptedStepId = $studentApplication->department_application_step_id;

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), confirmationPayload([
            'passport_photos_confirmed' => false,
        ]))
        ->assertRedirect(route('enrolments.confirm', $studentApplication))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');

    $classList = ClassList::query()->where('student_application_id', $studentApplication->id)->first();

    expect($classList?->type)->toBe(ClassListTypeEnum::VERIFIED)
        ->and($studentApplication->fresh()->department_application_step_id)->toBe($acceptedStepId)
        ->and(StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->exists())->toBeFalse();
});

it('still requires identity names and disability for provisional verification', function () {
    Queue::fake();

    $studentApplication = createVerifiedStudentApplication('CL-VERIFY-001');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);
    ClassList::query()->where('student_application_id', $studentApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
        'attributes' => [
            'identity_confirmed' => false,
            'disability_confirmed' => false,
            'names_confirmed' => false,
        ],
    ]);

    $this->actingAs($user)
        ->from(route('enrolments.verify', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), verificationPayload([
            'identity_confirmed' => false,
            'names_confirmed' => true,
            'disability_confirmed' => true,
        ]))
        ->assertRedirect(route('enrolments.verify', $studentApplication))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');

    expect(ClassList::query()->where('student_application_id', $studentApplication->id)->value('type'))
        ->toBe(ClassListTypeEnum::PROVISIONAL);

    Queue::assertNothingPushed();
});

it('verifies a provisional student when identity names and disability are confirmed', function () {
    Queue::fake();

    $studentApplication = createVerifiedStudentApplication('CL-VERIFY-002');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);
    ClassList::query()->where('student_application_id', $studentApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);
    $acceptedStep = resolveDepartmentApplicationStep($studentApplication, WorkflowStepEnum::ACCEPTED);

    $this->actingAs($user)
        ->from(route('enrolments.verify', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), verificationPayload())
        ->assertRedirect(route('enrolments.verify', $studentApplication))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    $classList = ClassList::query()->where('student_application_id', $studentApplication->id)->first();

    expect($classList?->type)->toBe(ClassListTypeEnum::VERIFIED)
        ->and($studentApplication->fresh()->department_application_step_id)->toBe($acceptedStep->id);

    Queue::assertPushed(SendOfferLetterJob::class);
});

it('flashes the enrolment resolution exception message when academic calendar is missing', function () {
    $studentApplication = createVerifiedStudentApplication('CL-CONFIRM-NO-CAL');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);
    createEnrolledDepartmentStep($studentApplication);

    AcademicCalendar::query()->delete();

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), confirmationPayload())
        ->assertRedirect(route('enrolments.confirm', $studentApplication))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');

    $error = session('error');

    expect($error)->toBeString()
        ->and($error)->toContain('No academic calendar was found')
        ->and($error)->not->toContain('All changes have been rolled back')
        ->and(ClassList::query()->where('student_application_id', $studentApplication->id)->value('type'))
        ->toBe(ClassListTypeEnum::VERIFIED)
        ->and(StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->exists())->toBeFalse();
});

it('flashes the missing department enrolled step cause instead of a generic rollback message', function () {
    $studentApplication = createVerifiedStudentApplication('CL-CONFIRM-NO-STEP');
    $user = actingAsClassListStaff((int) $studentApplication->tenant_id);

    WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::ENROLLED->slug()],
        [
            'name' => WorkflowStepEnum::ENROLLED->name(),
            'description' => WorkflowStepEnum::ENROLLED->description(),
            'position' => WorkflowStepEnum::ENROLLED->position(),
        ],
    );

    DepartmentApplicationStep::query()
        ->where('institution_department_id', $studentApplication->institution_department_id)
        ->whereHas('workflowStep', fn ($query) => $query->where('slug', WorkflowStepEnum::ENROLLED->slug()))
        ->delete();

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $studentApplication))
        ->put(route('enrolments.update-class-list', $studentApplication), confirmationPayload())
        ->assertRedirect(route('enrolments.confirm', $studentApplication))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');

    $error = session('error');

    expect($error)->toBeString()
        ->and($error)->toContain('Department application step for workflow "enrolled" was not found')
        ->and($error)->not->toContain('All changes have been rolled back')
        ->and(ClassList::query()->where('student_application_id', $studentApplication->id)->value('type'))
        ->toBe(ClassListTypeEnum::VERIFIED)
        ->and(StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->exists())->toBeFalse();
});
