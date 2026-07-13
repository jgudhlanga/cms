<?php

use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__.'/../Maintenance/MaintenanceControllerTest.php';
require_once __DIR__.'/../Maintenance/MaintenanceUserPurgeTest.php';

it('flushes expired account purge archives', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => 'Expired archive flush test reason.',
    ])->assertNoContent();

    $archive = AccountPurgeArchive::query()->where('original_user_id', $studentUser->id)->first();
    expect($archive)->not->toBeNull();

    $archive->update(['flush_after' => now()->subDay()]);

    Artisan::call('account-purge-archives:flush-expired');

    $archive->refresh();

    expect($archive->flushed_at)->not->toBeNull()
        ->and($archive->payload)->toBe([]);
});

it('keeps archives that have not yet expired', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => 'Active archive retention test reason.',
    ])->assertNoContent();

    $archive = AccountPurgeArchive::query()->where('original_user_id', $studentUser->id)->first();
    expect($archive)->not->toBeNull();

    Artisan::call('account-purge-archives:flush-expired');

    $archive->refresh();

    expect($archive->flushed_at)->toBeNull()
        ->and($archive->payload)->not->toBe([]);
});

it('creates archive and note when purging maintenance user', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);
    $reason = 'Test account no longer needed after intake closure.';

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => $reason,
    ])->assertNoContent();

    $archive = AccountPurgeArchive::query()
        ->where('original_user_id', $studentUser->id)
        ->first();

    expect($archive)->not->toBeNull()
        ->and($archive->purge_type)->toBe(AccountPurgeTypeEnum::USER_ACCOUNT)
        ->and($archive->payload)->toBeArray()
        ->and($archive->payload['user']['id'] ?? null)->toBe($studentUser->id);

    $note = StudentNote::query()->find($archive->student_note_id);
    expect($note)->not->toBeNull()
        ->and($note->body)->toBe($reason)
        ->and($note->noteable_type)->toBe(User::class)
        ->and($note->noteable_id)->toBe($studentUser->id);
});

it('requires reason for maintenance user purge', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});
