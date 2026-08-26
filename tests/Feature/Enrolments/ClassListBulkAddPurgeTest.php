<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentIntakeClassSize;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

function makeClassListMutationUser(array $permissions): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function classListEntryFor(string $tracking, ?ClassListTypeEnum $type = null): array
{
    $application = createVerifiedStudentApplication($tracking);
    $entry = ClassList::query()->where('student_application_id', $application->id)->first();

    if ($type !== null && $entry !== null) {
        $entry->update(['type' => $type->value]);
        $entry->refresh();
    }

    return compact('application', 'entry');
}

it('bulk adds selected applications to the provisional class list with added audit', function () {
    Queue::fake();

    $first = createVerifiedStudentApplication('BULK-ADD-001');
    ClassList::query()->where('student_application_id', $first->id)->forceDelete();

    $second = createVerifiedStudentApplication('BULK-ADD-002');
    ClassList::query()->where('student_application_id', $second->id)->forceDelete();

    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$first->id, $second->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->whereIn('student_application_id', [$first->id, $second->id])->count())->toBe(2);

    $activity = Activity::query()
        ->where('log_name', 'ClassList')
        ->where('event', 'added')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->properties->get('to_type'))->toBe(ClassListTypeEnum::PROVISIONAL->value);
});

it('skips applications already on a class list during bulk add', function () {
    Queue::fake();

    $application = createVerifiedStudentApplication('BULK-ADD-EXISTING');
    $user = makeClassListMutationUser(['create:class-lists']);

    $before = ClassList::query()->where('student_application_id', $application->id)->count();

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$application->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->count())->toBe($before);
});

it('requires a note when bypass_ranking is set on bulk add', function () {
    Queue::fake();

    $application = createVerifiedStudentApplication('BYPASS-NOTE-001');
    ClassList::query()->where('student_application_id', $application->id)->forceDelete();
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$application->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'bypass_ranking' => true,
            'note' => 'short',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('note');
});

it('records bypass_ranking on audited bulk add', function () {
    Queue::fake();

    $application = createVerifiedStudentApplication('BYPASS-ADD-001');
    ClassList::query()->where('student_application_id', $application->id)->forceDelete();
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$application->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'bypass_ranking' => true,
            'note' => 'Manual override for equity placement after appeal.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $activity = Activity::query()
        ->where('log_name', 'ClassList')
        ->where('event', 'added')
        ->latest('id')
        ->first();

    expect($activity?->properties->get('bypass_ranking'))->toBeTrue()
        ->and($activity?->properties->get('note'))->toContain('equity');
});

it('transitions provisional to waiting and back to provisional', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-WAIT-001', ClassListTypeEnum::PROVISIONAL);
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::WAITING->value,
            'note' => 'Moved to waiting pending document clarification.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::WAITING);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::PROVISIONAL->value,
            'note' => 'Documents clarified — restoring provisional seat.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::PROVISIONAL);
});

it('transitions provisional to verified only with verify permission', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-VER-001', ClassListTypeEnum::PROVISIONAL);

    $forbidden = makeClassListMutationUser(['create:class-lists']);
    $this->actingAs($forbidden)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::VERIFIED->value,
            'note' => 'Attempt elevate without verify permission should fail.',
        ])
        ->assertForbidden();

    $allowed = makeClassListMutationUser(['verify:class-lists']);
    $this->actingAs($allowed)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::VERIFIED->value,
            'note' => 'Elevated after desk verification completed.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::VERIFIED);
});

it('transitions verified to final only with manage-final permission', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-FINAL-001', ClassListTypeEnum::VERIFIED);

    $forbidden = makeClassListMutationUser(['verify:class-lists']);
    $this->actingAs($forbidden)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::FINAL->value,
            'note' => 'Attempt final without manage-final should fail.',
        ])
        ->assertForbidden();

    $allowed = makeClassListMutationUser(['manage-final:class-lists']);
    $this->actingAs($allowed)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::FINAL->value,
            'note' => 'Final enrolment confirmed after payment match.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::FINAL);
});

it('rejects illegal transitions such as final to waiting', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-ILLEGAL-001', ClassListTypeEnum::FINAL);
    $user = makeClassListMutationUser(['manage-final:class-lists', 'create:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::WAITING->value,
            'note' => 'Illegal demote path should be rejected by matrix.',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('to_type');
});

it('requires a note when failing an application', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-FAIL-NOTE-001', ClassListTypeEnum::PROVISIONAL);
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::FAILED->value,
            'note' => 'short',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('note');
});

it('requires a note when demoting class list status', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-DEMOTE-NOTE-001', ClassListTypeEnum::PROVISIONAL);
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::WAITING->value,
            'note' => 'too-short',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('note');
});

it('force purges class list entries with a required note and audit trail', function () {
    $application = createVerifiedStudentApplication('PURGE-PROV-001');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $entryId = ClassList::query()->where('student_application_id', $application->id)->value('id');
    $user = makeClassListMutationUser(['delete:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.purge-class-list'), [
            'application_ids' => [$application->id],
            'note' => 'Removed after applicant transferred to another programme.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::withTrashed()->whereKey($entryId)->exists())->toBeFalse();

    $activity = Activity::query()
        ->where('log_name', 'ClassList')
        ->where('event', 'purged')
        ->where('subject_id', $entryId)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->properties->get('note'))->toContain('transferred')
        ->and($activity?->properties->get('student_application_id'))->toBe($application->id);
});

it('rejects purge of final class list entries', function () {
    $application = createVerifiedStudentApplication('PURGE-FINAL-LOCKED-001');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::FINAL->value,
    ]);

    $user = makeClassListMutationUser(['delete:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.purge-class-list'), [
            'application_ids' => [$application->id],
            'note' => 'Attempt to remove a locked final class list entry.',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('application_ids');

    expect(ClassList::query()->where('student_application_id', $application->id)->exists())->toBeTrue();
});

it('rejects transitions away from final class list status', function () {
    Queue::fake();
    ['application' => $application] = classListEntryFor('TRANS-FINAL-LOCK-001', ClassListTypeEnum::FINAL);
    $user = makeClassListMutationUser(['manage-final:class-lists', 'verify:class-lists', 'create:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$application->id],
            'to_type' => ClassListTypeEnum::VERIFIED->value,
            'note' => 'Final entries must remain locked against further edits.',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('to_type');
});

it('bulk transitions multiple verified entries to final with one note', function () {
    Queue::fake();
    ['application' => $first] = classListEntryFor('TRANS-BULK-FINAL-001', ClassListTypeEnum::VERIFIED);
    ['application' => $second] = classListEntryFor('TRANS-BULK-FINAL-002', ClassListTypeEnum::VERIFIED);
    $user = makeClassListMutationUser(['manage-final:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.transition-class-list'), [
            'application_ids' => [$first->id, $second->id],
            'to_type' => ClassListTypeEnum::FINAL->value,
            'note' => 'Bulk elevate verified applicants to the final class list.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $first->id)->value('type'))
        ->toBe(ClassListTypeEnum::FINAL);
    expect(ClassList::query()->where('student_application_id', $second->id)->value('type'))
        ->toBe(ClassListTypeEnum::FINAL);
});

it('requires a note of at least 10 characters to purge', function () {
    $application = createVerifiedStudentApplication('PURGE-NOTE-001');
    $user = makeClassListMutationUser(['delete:class-lists']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('enrolments.purge-class-list'), [
            'application_ids' => [$application->id],
            'note' => 'short',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('note');

    expect(ClassList::query()->where('student_application_id', $application->id)->exists())->toBeTrue();
});

it('forbids purge without delete permission', function () {
    $application = createVerifiedStudentApplication('PURGE-FORBIDDEN-001');
    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.purge-class-list'), [
            'application_ids' => [$application->id],
            'note' => 'Attempted purge without permission should fail.',
        ])
        ->assertForbidden();
});

it('bulk adds provisional and waiting application ids in one request', function () {
    Queue::fake();

    $provisional = createVerifiedStudentApplication('BULK-MIX-PROV');
    ClassList::query()->where('student_application_id', $provisional->id)->forceDelete();

    $waiting = createVerifiedStudentApplication('BULK-MIX-WAIT');
    ClassList::query()->where('student_application_id', $waiting->id)->forceDelete();

    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [$provisional->id],
            'waiting_application_ids' => [$waiting->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ClassList::query()->where('student_application_id', $provisional->id)->value('type'))
        ->toBe(ClassListTypeEnum::PROVISIONAL)
        ->and(ClassList::query()->where('student_application_id', $waiting->id)->value('type'))
        ->toBe(ClassListTypeEnum::WAITING);
});

it('allows waiting list add without note when provisional seats already fill the intake limit', function () {
    Queue::fake();

    $seatHolder = createVerifiedStudentApplication('WAIT-OVER-SEAT');
    ClassList::query()->where('student_application_id', $seatHolder->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $waitingApplicant = createVerifiedStudentApplication('WAIT-OVER-ADD');
    ClassList::query()->where('student_application_id', $waitingApplicant->id)->forceDelete();

    // Align the waiting applicant onto the same intake context as the seat holder.
    $waitingApplicant->update([
        'institution_department_id' => $seatHolder->institution_department_id,
        'department_level_id' => $seatHolder->department_level_id,
        'department_course_id' => $seatHolder->department_course_id,
        'intake_period_id' => $seatHolder->intake_period_id,
        'mode_of_study_id' => $seatHolder->mode_of_study_id,
    ]);

    DepartmentIntakeClassSize::query()->create([
        'tenant_id' => $seatHolder->tenant_id,
        'institution_department_id' => $seatHolder->institution_department_id,
        'department_course_id' => $seatHolder->department_course_id,
        'department_level_id' => $seatHolder->department_level_id,
        'intake_period_id' => $seatHolder->intake_period_id,
        'mode_of_study_id' => $seatHolder->mode_of_study_id,
        'class_size' => 1,
    ]);

    $user = makeClassListMutationUser(['create:class-lists']);

    $this->actingAs($user)
        ->post(route('enrolments.bulk-add-to-class-list'), [
            'application_ids' => [],
            'waiting_application_ids' => [$waitingApplicant->id],
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'institution_department_id' => $seatHolder->institution_department_id,
            'department_level_id' => $seatHolder->department_level_id,
            'department_course_id' => $seatHolder->department_course_id,
            'intake_period_id' => $seatHolder->intake_period_id,
            'mode_of_study_id' => $seatHolder->mode_of_study_id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    expect(ClassList::query()->where('student_application_id', $waitingApplicant->id)->value('type'))
        ->toBe(ClassListTypeEnum::WAITING);
});
