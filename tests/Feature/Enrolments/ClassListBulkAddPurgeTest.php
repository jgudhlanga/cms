<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Enrolments\ClassList;
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

it('bulk adds selected applications to the provisional class list', function () {
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

it('force purges class list entries of any type with a required note and audit trail', function () {
    $application = createVerifiedStudentApplication('PURGE-FINAL-001');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::FINAL->value,
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
