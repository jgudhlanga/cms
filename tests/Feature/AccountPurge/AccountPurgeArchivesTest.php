<?php

use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Acl\Permission;
use App\Models\Students\Student;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

require_once __DIR__.'/../Maintenance/MaintenanceControllerTest.php';
require_once __DIR__.'/../Maintenance/MaintenanceUserPurgeTest.php';
require_once __DIR__.'/../Students/StudentAccountPurgeTest.php';

const ARCHIVE_PURGE_REASON = 'Valid maintenance purge reason for archive testing.';

function purgeNoProfileUserForArchiveTest(int $tenantId): AccountPurgeArchive
{
    $studentUser = createNoProfileStudentUser($tenantId);

    test()->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => ARCHIVE_PURGE_REASON,
    ])->assertNoContent();

    $archive = AccountPurgeArchive::query()->where('original_user_id', $studentUser->id)->first();
    expect($archive)->not->toBeNull();

    return $archive;
}

it('returns unauthorized for guests on archives list endpoint', function (): void {
    $this->getJson(route('maintenance.account-purge-archives'))
        ->assertUnauthorized();
});

it('forbids users without root manage from archives list endpoint', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('maintenance.account-purge-archives'))
        ->assertForbidden();
});

it('lists tenant-scoped account purge archives', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);

    $response = $this->getJson(route('maintenance.account-purge-archives'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'attributes' => [
                        'purgeType',
                        'purgeTypeLabel',
                        'status',
                        'statusLabel',
                        'name',
                        'email',
                        'canRestore',
                        'canFlush',
                    ],
                ],
            ],
            'links',
            'meta',
        ]);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($archive->id);
});

it('filters archives by status and purge type', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);

    $this->getJson(route('maintenance.account-purge-archives', [
        'status' => 'active',
        'purge_type' => 'user_account',
    ]))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $archive->id]);

    $archive->update(['flushed_at' => now(), 'payload' => []]);

    $this->getJson(route('maintenance.account-purge-archives', ['status' => 'active']))
        ->assertSuccessful();

    $activeIds = collect($this->getJson(route('maintenance.account-purge-archives', ['status' => 'active']))->json('data'))
        ->pluck('id')
        ->all();

    expect($activeIds)->not->toContain($archive->id);
});

it('manually flushes an active archive', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);
    $noteId = $archive->student_note_id;

    $this->deleteJson(route('maintenance.account-purge-archives.flush', $archive))
        ->assertNoContent();

    $archive->refresh();

    expect($archive->flushed_at)->not->toBeNull()
        ->and($archive->payload)->toBe([])
        ->and(StudentNote::query()->whereKey($noteId)->exists())->toBeFalse();
});

it('rejects flushing an already flushed archive', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);

    $archive->update([
        'flushed_at' => now(),
        'payload' => [],
    ]);

    $this->deleteJson(route('maintenance.account-purge-archives.flush', $archive))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('archive');
});

it('restores a purged user archive with iso8601 datetime payload values', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);
    $originalUserId = $archive->original_user_id;

    $payload = $archive->payload;
    $payload['user']['created_at'] = '2026-03-19T15:12:16.000000Z';
    $payload['user']['updated_at'] = '2026-03-19T15:54:37.000000Z';
    $payload['user']['email_verified_at'] = '2026-03-19T15:12:16.000000Z';
    $payload['user']['last_login_at'] = '2026-03-19T17:54:37.000000Z';
    $archive->update(['payload' => $payload]);

    $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertSuccessful()
        ->assertJsonPath('data.userId', $originalUserId);

    $restoredUser = User::query()->find($originalUserId);

    expect($restoredUser)->not->toBeNull()
        ->and($restoredUser?->created_at?->format('Y-m-d H:i:s'))->toBe('2026-03-19 15:12:16');
});

it('restores a purged user archive', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);
    $originalUserId = $archive->original_user_id;
    $email = $archive->summary['email'] ?? null;

    expect(User::query()->whereKey($originalUserId)->exists())->toBeFalse();

    $response = $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertSuccessful()
        ->assertJsonPath('data.userId', $originalUserId);

    $archive->refresh();
    $restoredUser = User::query()->find($originalUserId);

    expect($archive->restored_at)->not->toBeNull()
        ->and($restoredUser)->not->toBeNull()
        ->and($restoredUser?->email)->toBe($email)
        ->and(Hash::check('invalid-password', (string) $restoredUser?->password))->toBeFalse();
});

it('restores a purged student archive', function (): void {
    $rootUser = actingAsRootStudentPurgeUser();
    $student = createStudentForPurge($rootUser->tenant_id);
    $studentId = $student->id;
    $userId = $student->user_id;
    $studentNumber = $student->student_number;

    $this->deleteJson(route('students.purge', $student), [
        'reason' => 'Archive restore integration test reason.',
    ])->assertRedirect(route('students.index'));

    $archive = AccountPurgeArchive::query()->where('original_student_id', $studentId)->first();
    expect($archive)->not->toBeNull()
        ->and($archive->purge_type)->toBe(AccountPurgeTypeEnum::STUDENT_ACCOUNT);

    $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertSuccessful()
        ->assertJsonPath('data.studentId', $studentId)
        ->assertJsonPath('data.userId', $userId);

    $archive->refresh();

    expect($archive->restored_at)->not->toBeNull()
        ->and(Student::query()->whereKey($studentId)->exists())->toBeTrue()
        ->and(User::query()->whereKey($userId)->exists())->toBeTrue()
        ->and(Student::query()->find($studentId)?->student_number)->toBe($studentNumber);
});

it('rejects restoring a flushed archive', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);

    $archive->update([
        'flushed_at' => now(),
        'payload' => [],
    ]);

    $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertStatus(422)
        ->assertJsonPath('errorCode', 'not_restorable');
});

it('rejects restoring when email already exists', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $archive = purgeNoProfileUserForArchiveTest($rootUser->tenant_id);
    $email = $archive->summary['email'] ?? 'conflict@example.com';

    User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'email' => $email,
    ]);

    $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertStatus(422)
        ->assertJsonPath('errorCode', 'email_conflict');
});

it('forbids cross-tenant archive restore', function (): void {
    Permission::findOrCreate('root:manage', 'web');

    $otherRoot = User::factory()->create();
    $otherRoot->givePermissionTo('root:manage');
    test()->actingAs($otherRoot);

    $archive = purgeNoProfileUserForArchiveTest($otherRoot->tenant_id);

    $rootUser = actingAsRootMaintenanceUser();
    expect($rootUser->tenant_id)->not->toBe($archive->tenant_id);

    $this->postJson(route('maintenance.account-purge-archives.restore', $archive))
        ->assertForbidden();
});
