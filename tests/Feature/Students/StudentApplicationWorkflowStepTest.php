<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Rbac\Permission;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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

    foreach (WorkflowStepEnum::cases() as $step) {
        resolveWorkflowStep($step);
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('stores the review workflow step id when an application is submitted', function () {
    $application = createVerifiedStudentApplication('WF-SUBMIT-001');
    $application->update(['workflow_step_id' => null]);

    Helper::initializeProgramWorkflow($application);

    $reviewStep = WorkflowStep::query()->where('slug', WorkflowStepEnum::REVIEW->slug())->first();

    expect($application->fresh()->workflow_step_id)->toBe($reviewStep?->id)
        ->and(Schema::hasTable('department_application_steps'))->toBeFalse();
});

it('advances a class list confirmation to the enrolled workflow step id', function () {
    $application = createVerifiedStudentApplication('WF-ENROLLED-001');
    $user = actingAsWorkflowClassListStaff((int) $application->tenant_id);
    $enrolledStep = resolveWorkflowStep(WorkflowStepEnum::ENROLLED);

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $application))
        ->put(route('enrolments.update-class-list', $application), [
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
        ])
        ->assertRedirect(route('enrolments.confirm', $application))
        ->assertSessionHas('success');

    expect($application->fresh()->workflow_step_id)->toBe($enrolledStep->id)
        ->and(Schema::hasTable('department_application_steps'))->toBeFalse();
});

it('writes the rejected workflow step id when a class list application is rejected', function () {
    $application = createVerifiedStudentApplication('WF-REJECT-001');
    $user = actingAsWorkflowClassListStaff((int) $application->tenant_id);
    $rejectedStep = resolveWorkflowStep(WorkflowStepEnum::REJECTED);

    $this->actingAs($user)
        ->from(route('enrolments.confirm', $application))
        ->put(route('enrolments.reject-application', $application))
        ->assertRedirect(route('enrolments.confirm', $application))
        ->assertSessionHas('success');

    expect($application->fresh()->workflow_step_id)->toBe($rejectedStep->id)
        ->and(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::FAILED)
        ->and(Schema::hasTable('department_application_steps'))->toBeFalse();
});

function actingAsWorkflowClassListStaff(?int $tenantId = null): User
{
    $user = User::factory()->create(array_filter([
        'tenant_id' => $tenantId,
    ]));
    Permission::findOrCreate('manage-final:class-lists', 'web');
    Permission::findOrCreate('confirm:class-lists', 'web');
    Permission::findOrCreate('verify:class-lists', 'web');
    $user->givePermissionTo(['manage-final:class-lists', 'confirm:class-lists', 'verify:class-lists']);

    return $user;
}
