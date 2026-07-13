<?php

use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Acl\Permission;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Support\Str;

require_once __DIR__.'/../Maintenance/MaintenanceControllerTest.php';

function actingAsRootStudentPurgeUser(): User
{
    Permission::findOrCreate('root:manage', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('root:manage');
    test()->actingAs($user);

    return $user;
}

function createStudentForPurge(int $tenantId): Student
{
    $studentUser = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Purge',
        'last_name' => 'Student',
    ]);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID']);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'student_number' => 'PURGE-STU-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);
}

it('returns unauthorized for guests on student purge endpoint', function (): void {
    $student = createStudentForPurge(User::factory()->create()->tenant_id);

    $this->deleteJson(route('students.purge', $student), [
        'reason' => 'Valid purge reason for testing.',
    ])->assertUnauthorized();
});

it('forbids users without root manage from student purge endpoint', function (): void {
    $actor = User::factory()->create();
    $student = createStudentForPurge($actor->tenant_id);

    $this->actingAs($actor)
        ->deleteJson(route('students.purge', $student), [
            'reason' => 'Valid purge reason for testing.',
        ])
        ->assertForbidden();
});

it('requires a purge reason for student purge', function (): void {
    $rootUser = actingAsRootStudentPurgeUser();
    $student = createStudentForPurge($rootUser->tenant_id);

    $this->deleteJson(route('students.purge', $student), [
        'reason' => 'short',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    expect(Student::query()->whereKey($student->id)->exists())->toBeTrue();
});

it('purges a student account and creates archive and note', function (): void {
    $rootUser = actingAsRootStudentPurgeUser();
    $student = createStudentForPurge($rootUser->tenant_id);
    $userId = $student->user_id;
    $reason = 'Duplicate account created in error during registration.';

    $this->deleteJson(route('students.purge', $student), [
        'reason' => $reason,
    ])->assertRedirect(route('students.index'));

    expect(Student::query()->whereKey($student->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($userId)->exists())->toBeFalse();

    $archive = AccountPurgeArchive::query()
        ->where('original_student_id', $student->id)
        ->first();

    expect($archive)->not->toBeNull()
        ->and($archive->purge_type)->toBe(AccountPurgeTypeEnum::STUDENT_ACCOUNT)
        ->and($archive->purged_by)->toBe($rootUser->id)
        ->and($archive->payload)->toBeArray()
        ->and($archive->payload['student']['id'] ?? null)->toBe($student->id);

    $note = StudentNote::query()->find($archive->student_note_id);
    expect($note)->not->toBeNull()
        ->and($note->body)->toBe($reason)
        ->and($note->created_by)->toBe($rootUser->id)
        ->and($note->updated_by)->toBe($rootUser->id);
});

it('forbids purging a student from another tenant', function (): void {
    $rootUser = actingAsRootStudentPurgeUser();
    $student = createStudentForPurge(User::factory()->create()->tenant_id);

    $this->deleteJson(route('students.purge', $student), [
        'reason' => 'Valid purge reason for testing.',
    ])->assertForbidden();

    expect(Student::query()->whereKey($student->id)->exists())->toBeTrue();
});

it('redirects to users index when purging from users context', function (): void {
    $rootUser = actingAsRootStudentPurgeUser();
    $student = createStudentForPurge($rootUser->tenant_id);

    $this->deleteJson(route('students.purge', $student).'?from=users', [
        'reason' => 'Valid purge reason for testing.',
    ])->assertRedirect(route('users.index'));
});
