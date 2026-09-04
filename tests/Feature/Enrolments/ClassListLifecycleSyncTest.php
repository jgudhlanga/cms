<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\Enrolments\ClassList;
use App\Models\Rbac\Permission;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    ensureApplicationWorkflowSteps();
});

function lifecycleClassListUser(array $permissions): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function lifecycleClassListEntry(string $tracking, ClassListTypeEnum $type): StudentApplication
{
    $application = createVerifiedStudentApplication($tracking);
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => $type->value,
    ]);

    return $application->fresh(['student', 'classList', 'workflowStep']);
}

it('moves a provisional application to waiting with waitlisted workflow and progress email', function () {
    Queue::fake();
    $application = lifecycleClassListEntry('LIFE-WAIT-001', ClassListTypeEnum::PROVISIONAL);
    $waitlisted = resolveWorkflowStep(WorkflowStepEnum::WAITLISTED);
    $user = lifecycleClassListUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::WAITING->value,
            'note' => 'Moved to waiting pending document clarification.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::WAITING)
        ->and($application->fresh()->workflow_step_id)->toBe($waitlisted->id);

    Queue::assertPushed(SendEnrolmentProgressJob::class);
});

it('runs the full verification lifecycle from the class list transition', function () {
    Queue::fake();
    $application = lifecycleClassListEntry('LIFE-VER-001', ClassListTypeEnum::PROVISIONAL);
    $application->student->update([
        'student_number' => null,
        'student_number_generated' => false,
    ]);
    $accepted = resolveWorkflowStep(WorkflowStepEnum::ACCEPTED);
    $user = lifecycleClassListUser(['verify:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::VERIFIED->value,
            'note' => 'Elevated after desk verification completed.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $student = $application->student->fresh();

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::VERIFIED)
        ->and($application->fresh()->workflow_step_id)->toBe($accepted->id)
        ->and($student?->student_number)->not->toBeNull()
        ->and($student?->student_number_generated)->toBeTrue()
        ->and(StudentEnrolment::query()->where('student_application_id', $application->id)->exists())->toBeFalse();

    Queue::assertPushed(SendOfferLetterJob::class);
});

it('creates an enrolment and fills a missing student number when moving verified to final', function () {
    Queue::fake();
    ensureStudentEnrolmentResolutionFixtures();
    $application = lifecycleClassListEntry('LIFE-FINAL-001', ClassListTypeEnum::VERIFIED);
    $application->student->update([
        'student_number' => null,
        'student_number_generated' => false,
    ]);
    $enrolled = resolveWorkflowStep(WorkflowStepEnum::ENROLLED);
    $user = lifecycleClassListUser(['manage-final:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::FINAL->value,
            'note' => 'Final enrolment confirmed after payment match.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $student = $application->student->fresh();

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::FINAL)
        ->and($application->fresh()->workflow_step_id)->toBe($enrolled->id)
        ->and($student?->student_number)->not->toBeNull()
        ->and($student?->student_number_generated)->toBeTrue()
        ->and(StudentEnrolment::query()->where('student_application_id', $application->id)->exists())->toBeTrue();
});

it('fails an application by marking the class list failed and workflow rejected', function () {
    Queue::fake();
    $application = lifecycleClassListEntry('LIFE-FAIL-001', ClassListTypeEnum::PROVISIONAL);
    $rejected = resolveWorkflowStep(WorkflowStepEnum::REJECTED);
    $user = lifecycleClassListUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::FAILED->value,
            'note' => 'Applicant did not meet programme entry requirements.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::FAILED)
        ->and($application->fresh()->workflow_step_id)->toBe($rejected->id);

    Queue::assertPushed(SendEnrolmentProgressJob::class);
});

it('removes the class list row and resets the application workflow to review', function () {
    $application = lifecycleClassListEntry('LIFE-PURGE-001', ClassListTypeEnum::PROVISIONAL);
    $review = resolveWorkflowStep(WorkflowStepEnum::REVIEW);
    $entryId = ClassList::query()->where('student_application_id', $application->id)->value('id');
    $user = lifecycleClassListUser(['delete:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.purge-class-list'), [
            'application_ids' => [$application->id],
            'note' => 'Removed after applicant transferred to another programme.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::withTrashed()->whereKey($entryId)->exists())->toBeFalse()
        ->and(StudentApplication::query()->whereKey($application->id)->exists())->toBeTrue()
        ->and($application->fresh()->workflow_step_id)->toBe($review->id);
});

it('sets requirements when adding an application to the provisional class list', function () {
    Queue::fake();
    $application = createVerifiedStudentApplication('LIFE-ADD-001');
    ClassList::query()->where('student_application_id', $application->id)->forceDelete();
    $requirements = resolveWorkflowStep(WorkflowStepEnum::REQUIREMENTS);
    $user = lifecycleClassListUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$application->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::PROVISIONAL)
        ->and($application->fresh()->workflow_step_id)->toBe($requirements->id);

    Queue::assertPushed(SendEnrolmentProgressJob::class);
});
