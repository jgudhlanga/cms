<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Students\Student;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountPurgeArchiveService
{
    public function __construct(
        private readonly AccountPurgeSnapshotBuilder $snapshotBuilder,
        private readonly UserAccountRelationPurgeService $userRelationPurgeService,
        private readonly StudentRelationPurgeService $studentRelationPurgeService,
    ) {}

    public function purgeUserAccount(
        User $user,
        User $purgedBy,
        string $reason,
        int $tenantId,
    ): AccountPurgeArchive {
        return DB::transaction(function () use ($user, $purgedBy, $reason, $tenantId): AccountPurgeArchive {
            $note = $this->createPurgeNote($user, $reason);
            $payload = $this->snapshotBuilder->buildForUser($user);

            $archive = $this->createArchive(
                purgeType: AccountPurgeTypeEnum::USER_ACCOUNT,
                purgedBy: $purgedBy,
                tenantId: $tenantId,
                note: $note,
                originalUserId: $user->id,
                originalStudentId: null,
                summary: $this->buildUserSummary($user),
                payload: $payload,
            );

            $this->userRelationPurgeService->purge($user);
            $user->forceDelete();

            return $archive;
        });
    }

    public function purgeStudentAccount(
        Student $student,
        User $purgedBy,
        string $reason,
        int $tenantId,
    ): AccountPurgeArchive {
        return DB::transaction(function () use ($student, $purgedBy, $reason, $tenantId): AccountPurgeArchive {
            $student->loadMissing('user');
            $user = $student->user;

            $note = $this->createPurgeNote($student, $reason);
            $payload = $this->snapshotBuilder->buildForStudent($student);

            $archive = $this->createArchive(
                purgeType: AccountPurgeTypeEnum::STUDENT_ACCOUNT,
                purgedBy: $purgedBy,
                tenantId: $tenantId,
                note: $note,
                originalUserId: $user?->id,
                originalStudentId: $student->id,
                summary: $this->buildStudentSummary($student, $user),
                payload: $payload,
            );

            $this->studentRelationPurgeService->purge($student, $note->id);

            if ($user !== null) {
                $user->notes()->whereKeyNot($note->id)->forceDelete();
                $this->userRelationPurgeService->purge($user);
            }

            $student->forceDelete();

            if ($user !== null) {
                $user->forceDelete();
            }

            return $archive;
        });
    }

    private function createPurgeNote(Model $noteable, string $reason): StudentNote
    {
        return $noteable->notes()->create([
            'title' => __('trans.account_purge_note_title'),
            'body' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $payload
     */
    private function createArchive(
        AccountPurgeTypeEnum $purgeType,
        User $purgedBy,
        int $tenantId,
        StudentNote $note,
        ?int $originalUserId,
        ?int $originalStudentId,
        array $summary,
        array $payload,
    ): AccountPurgeArchive {
        $purgedAt = now();

        return AccountPurgeArchive::query()->create([
            'tenant_id' => $tenantId,
            'purge_type' => $purgeType,
            'purged_by' => $purgedBy->id,
            'student_note_id' => $note->id,
            'original_user_id' => $originalUserId,
            'original_student_id' => $originalStudentId,
            'summary' => $summary,
            'payload' => $payload,
            'payload_version' => AccountPurgeSnapshotBuilder::PAYLOAD_VERSION,
            'purged_at' => $purgedAt,
            'flush_after' => $purgedAt->copy()->addDays((int) config('purge.archive_retention_days', 30)),
        ]);
    }

    /**
     * @return array{name: string, email: string|null, student_number: string|null}
     */
    private function buildStudentSummary(Student $student, ?User $user): array
    {
        return [
            'name' => $user?->full_name ?? 'Unknown',
            'email' => $user?->email,
            'student_number' => $student->student_number,
        ];
    }

    /**
     * @return array{name: string, email: string|null, student_number: null}
     */
    private function buildUserSummary(User $user): array
    {
        return [
            'name' => $user->full_name,
            'email' => $user->email,
            'student_number' => null,
        ];
    }
}
