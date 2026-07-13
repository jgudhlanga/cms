<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Exceptions\AccountPurge\AccountPurgeArchiveRestoreException;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Students\Student;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountPurgeArchiveRestoreService
{
    /**
     * @return array{userId: int|null, studentId: int|null, studentProfileUrl: string|null, userProfileUrl: string|null}
     */
    public function restore(AccountPurgeArchive $archive): array
    {
        $this->assertRestorable($archive);

        $payload = $archive->payload ?? [];
        $this->assertPayloadVersion($payload);
        $this->assertNoConflicts($archive, $payload);
        $this->assertMediaFilesExist($payload);

        return DB::transaction(function () use ($archive, $payload): array {
            $result = $archive->purge_type === AccountPurgeTypeEnum::STUDENT_ACCOUNT
                ? $this->restoreStudentPayload($payload, $archive)
                : $this->restoreUserPayload($payload, $archive);

            $this->relinkPurgeNote($archive, $result['studentId'], $result['userId']);

            $archive->update(['restored_at' => now()]);

            return $result;
        });
    }

    private function assertRestorable(AccountPurgeArchive $archive): void
    {
        if (! $archive->isRestorable()) {
            throw new AccountPurgeArchiveRestoreException(
                __('trans.maintenance_archives_restore_not_restorable'),
                'not_restorable',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertPayloadVersion(array $payload): void
    {
        $version = $payload['version'] ?? null;

        if ($version !== AccountPurgeSnapshotBuilder::PAYLOAD_VERSION) {
            throw new AccountPurgeArchiveRestoreException(
                __('trans.maintenance_archives_restore_unsupported_version'),
                'unsupported_version',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertNoConflicts(AccountPurgeArchive $archive, array $payload): void
    {
        $tenantId = $archive->tenant_id;
        $userData = $payload['user'] ?? null;

        if (is_array($userData)) {
            $userId = $userData['id'] ?? null;
            $email = $userData['email'] ?? null;

            if ($userId !== null && User::query()->where('tenant_id', $tenantId)->whereKey($userId)->exists()) {
                throw new AccountPurgeArchiveRestoreException(
                    __('trans.maintenance_archives_restore_user_id_conflict'),
                    'user_id_conflict',
                );
            }

            if (is_string($email) && $email !== '' && User::query()->where('tenant_id', $tenantId)->where('email', $email)->exists()) {
                throw new AccountPurgeArchiveRestoreException(
                    __('trans.maintenance_archives_restore_email_conflict'),
                    'email_conflict',
                );
            }
        }

        $studentData = $payload['student'] ?? null;

        if (is_array($studentData)) {
            $studentId = $studentData['id'] ?? null;
            $studentNumber = $studentData['student_number'] ?? null;

            if ($studentId !== null && Student::query()->where('tenant_id', $tenantId)->whereKey($studentId)->exists()) {
                throw new AccountPurgeArchiveRestoreException(
                    __('trans.maintenance_archives_restore_student_id_conflict'),
                    'student_id_conflict',
                );
            }

            if (is_string($studentNumber) && $studentNumber !== '' && Student::query()->where('tenant_id', $tenantId)->where('student_number', $studentNumber)->exists()) {
                throw new AccountPurgeArchiveRestoreException(
                    __('trans.maintenance_archives_restore_student_number_conflict'),
                    'student_number_conflict',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertMediaFilesExist(array $payload): void
    {
        $paths = $this->collectMediaPaths($payload);
        $missing = [];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if (! File::exists($path)) {
                $missing[] = $path;
            }
        }

        if ($missing !== []) {
            throw new AccountPurgeArchiveRestoreException(
                __('trans.maintenance_archives_restore_missing_media'),
                'missing_media',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{userId: int|null, studentId: int|null, studentProfileUrl: string|null, userProfileUrl: string|null}
     */
    private function restoreUserPayload(array $payload, AccountPurgeArchive $archive): array
    {
        $userData = $payload['user'] ?? null;

        if (! is_array($userData)) {
            throw new AccountPurgeArchiveRestoreException(
                __('trans.maintenance_archives_restore_invalid_payload'),
                'invalid_payload',
            );
        }

        $userId = $this->restoreUserRow($userData);
        $user = User::query()->findOrFail($userId);

        $this->insertRow('user_preferences', $payload['preferences'] ?? null);
        $this->insertRows('application_fees', $payload['application_fees'] ?? []);
        $this->restoreLedgers($payload['ledgers'] ?? []);
        $this->restoreMediaRows($payload['media'] ?? []);
        $this->assignRolesAndPermissions($user, $payload['roles'] ?? [], $payload['permissions'] ?? []);

        return [
            'userId' => $userId,
            'studentId' => null,
            'studentProfileUrl' => null,
            'userProfileUrl' => route('users.show', $userId),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{userId: int|null, studentId: int|null, studentProfileUrl: string|null, userProfileUrl: string|null}
     */
    private function restoreStudentPayload(array $payload, AccountPurgeArchive $archive): array
    {
        $userId = null;
        $userData = $payload['user'] ?? null;

        if (is_array($userData)) {
            $userId = $this->restoreUserRow($userData);
        }

        $studentData = $payload['student'] ?? null;

        if (! is_array($studentData)) {
            throw new AccountPurgeArchiveRestoreException(
                __('trans.maintenance_archives_restore_invalid_payload'),
                'invalid_payload',
            );
        }

        if ($userId !== null) {
            $studentData['user_id'] = $userId;
        }

        $this->insertRow('students', $studentData);
        $studentId = (int) $studentData['id'];

        $preserveNoteId = $archive->student_note_id;

        $this->insertMorphRows('contacts', $payload['contacts'] ?? [], $preserveNoteId);
        $this->insertMorphRows('addresses', $payload['addresses'] ?? [], $preserveNoteId);
        $this->insertMorphRows('next_of_kin', $payload['next_of_kin'] ?? [], $preserveNoteId);
        $this->insertStudentNotes($payload['student_notes'] ?? [], $preserveNoteId);

        $this->restoreApplications($payload['applications'] ?? []);
        $this->insertRows('class_lists', $payload['class_lists'] ?? []);
        $this->insertRows('student_enrolments', $payload['enrolments'] ?? []);
        $this->insertRows('course_work_marks', $payload['course_work_marks'] ?? []);
        $this->insertRows('course_work_audit_logs', $payload['course_work_audit_logs'] ?? []);
        $this->insertRows('academic_calendar_student_enrolments', $payload['academic_calendar_student_enrolments'] ?? []);
        $this->insertRows('sponsors', $payload['sponsors'] ?? []);
        $this->insertRows('academic_records', $payload['academic_records'] ?? []);
        $this->insertRows('student_academic_results', $payload['student_academic_results'] ?? []);
        $this->insertRows('student_apprentices', $payload['student_apprentices'] ?? []);
        $this->insertRows('hostel_applications', $payload['hostel_applications'] ?? []);
        $this->insertRows('hostel_room_allocations', $payload['hostel_room_allocations'] ?? []);
        $this->insertRows('hostel_queries', $payload['hostel_queries'] ?? []);
        $this->insertRows('hostel_leaves', $payload['hostel_leaves'] ?? []);
        $this->insertRows('hostel_notice_student', $payload['hostel_notice_student'] ?? []);
        $this->insertRows('finance_transaction_queries', $payload['finance_transaction_queries'] ?? []);

        if ($userId !== null) {
            $user = User::query()->findOrFail($userId);
            $this->insertRow('user_preferences', $payload['preferences'] ?? null);
            $this->insertRows('application_fees', $payload['application_fees'] ?? []);
            $this->restoreLedgers($payload['ledgers'] ?? []);
            $this->restoreMediaRows($payload['media'] ?? []);
            $this->assignRolesAndPermissions($user, $payload['roles'] ?? [], $payload['permissions'] ?? []);
        }

        return [
            'userId' => $userId,
            'studentId' => $studentId,
            'studentProfileUrl' => route('students.show', $studentId),
            'userProfileUrl' => $userId !== null ? route('users.show', $userId) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    private function restoreUserRow(array $userData): int
    {
        $userData['password'] = Hash::make(Str::random(64));
        unset($userData['remember_token']);

        $this->insertRow('users', $userData);

        return (int) $userData['id'];
    }

    /**
     * @param  list<array<string, mixed>>  $applications
     */
    private function restoreApplications(array $applications): void
    {
        foreach ($applications as $application) {
            if (! is_array($application)) {
                continue;
            }

            $notes = $application['notes'] ?? [];
            $ledgers = $application['ledgers'] ?? [];
            $offerLetterMedia = $application['offer_letter_media'] ?? [];

            unset($application['notes'], $application['ledgers'], $application['offer_letter_media']);

            $this->insertRow('student_applications', $application);
            $this->insertRows('student_notes', is_array($notes) ? $notes : []);
            $this->restoreLedgers(is_array($ledgers) ? $ledgers : []);
            $this->restoreMediaRows(is_array($offerLetterMedia) ? $offerLetterMedia : []);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $ledgers
     */
    private function restoreLedgers(array $ledgers): void
    {
        foreach ($ledgers as $ledger) {
            if (! is_array($ledger)) {
                continue;
            }

            $receiptMedia = $ledger['receipt_media'] ?? [];
            $proofMedia = $ledger['proof_of_payment_media'] ?? null;

            unset($ledger['receipt_media'], $ledger['proof_of_payment_media']);

            $this->insertRow('ledgers', $ledger);
            $this->restoreMediaRows(is_array($receiptMedia) ? $receiptMedia : []);

            if (is_array($proofMedia)) {
                $this->restoreMediaRows([$proofMedia]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $mediaRows
     */
    private function restoreMediaRows(array $mediaRows): void
    {
        foreach ($mediaRows as $media) {
            if (! is_array($media)) {
                continue;
            }

            unset($media['path']);

            if (! isset($media['id'])) {
                continue;
            }

            if (Media::query()->whereKey($media['id'])->exists()) {
                continue;
            }

            $this->insertRow('media', $media);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertMorphRows(string $table, array $rows, ?int $preserveNoteId): void
    {
        if ($table === 'student_notes') {
            $this->insertStudentNotes($rows, $preserveNoteId);

            return;
        }

        $this->insertRows($table, $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertStudentNotes(array $rows, ?int $preserveNoteId): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($preserveNoteId !== null && (int) ($row['id'] ?? 0) === $preserveNoteId) {
                continue;
            }

            $this->insertRow('student_notes', $row);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->insertRow($table, $row);
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $row
     */
    private function insertRow(string $table, ?array $row): void
    {
        if ($row === null || $row === []) {
            return;
        }

        $row = $this->filterTableColumns($table, $row);

        if ($row === []) {
            return;
        }

        $row = $this->normalizeRowValues($row);

        if (isset($row['id']) && DB::table($table)->where('id', $row['id'])->exists()) {
            return;
        }

        DB::table($table)->insert($row);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRowValues(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = $this->normalizeScalarValue($value);
        }

        return $row;
    }

    private function normalizeScalarValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value) !== 1) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function filterTableColumns(string $table, array $row): array
    {
        static $columnCache = [];

        if (! isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        return array_intersect_key($row, array_flip($columnCache[$table]));
    }

    /**
     * @param  list<string>  $roles
     * @param  list<string>  $permissions
     */
    private function assignRolesAndPermissions(User $user, array $roles, array $permissions): void
    {
        $roleNames = array_values(array_filter($roles, fn ($role) => is_string($role) && $role !== ''));
        $permissionNames = array_values(array_filter($permissions, fn ($permission) => is_string($permission) && $permission !== ''));

        if ($roleNames !== []) {
            $existingRoles = Role::query()->whereIn('name', $roleNames)->pluck('name')->all();
            if ($existingRoles !== []) {
                $user->assignRole($existingRoles);
            }
        }

        if ($permissionNames !== []) {
            $existingPermissions = Permission::query()->whereIn('name', $permissionNames)->pluck('name')->all();
            if ($existingPermissions !== []) {
                $user->givePermissionTo($existingPermissions);
            }
        }
    }

    private function relinkPurgeNote(AccountPurgeArchive $archive, ?int $studentId, ?int $userId): void
    {
        if ($archive->student_note_id === null) {
            return;
        }

        $note = StudentNote::query()->find($archive->student_note_id);

        if ($note === null) {
            return;
        }

        if ($studentId !== null) {
            $note->update([
                'noteable_type' => Student::class,
                'noteable_id' => $studentId,
            ]);

            return;
        }

        if ($userId !== null) {
            $note->update([
                'noteable_type' => User::class,
                'noteable_id' => $userId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function collectMediaPaths(array $payload): array
    {
        $paths = [];

        $walker = function (mixed $value) use (&$paths, &$walker): void {
            if (! is_array($value)) {
                return;
            }

            if (isset($value['path']) && is_string($value['path'])) {
                $paths[] = $value['path'];
            }

            foreach ($value as $item) {
                if (is_array($item)) {
                    $walker($item);
                }
            }
        };

        $walker($payload);

        return array_values(array_unique($paths));
    }
}
