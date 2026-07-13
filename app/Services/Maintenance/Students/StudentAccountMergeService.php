<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\AccountPurge\UserAccountRelationPurgeService;
use Illuminate\Support\Facades\DB;

class StudentAccountMergeService
{
    public function __construct(
        private readonly StudentAccountMergePreviewService $previewService,
        private readonly UserAccountRelationPurgeService $userRelationPurgeService,
    ) {}

    public function merge(
        int $sourceStudentId,
        int $targetStudentId,
        int $survivorStudentId,
        string $idNumber,
    ): Student {
        $source = Student::query()->withTrashed()->with('user')->findOrFail($sourceStudentId);
        $preview = $this->previewService->build($source, $targetStudentId, $idNumber);
        $normalizedIdNumber = $preview['proposedIdNumber'];

        $sourceId = $preview['source']['student']->id;
        $targetId = $preview['target']['student']->id;

        if (! in_array($survivorStudentId, [$sourceId, $targetId], true)) {
            throw ValidationException::withMessages([
                'survivor_student_id' => [__('trans.maintenance_faulty_data_merge_invalid_survivor')],
            ]);
        }

        $survivor = Student::query()->withTrashed()->with('user')->findOrFail($survivorStudentId);
        $absorbedId = $survivorStudentId === $sourceId ? $targetId : $sourceId;
        $absorbed = Student::query()->withTrashed()->with('user')->findOrFail($absorbedId);

        return DB::transaction(function () use ($survivor, $absorbed, $normalizedIdNumber, $sourceId): Student {
            $this->reassignStudentForeignKeys($absorbed, $survivor);
            $this->reassignMorphRelations($absorbed, $survivor);
            $this->reassignUserLedgers($absorbed->user, $survivor->user);
            $this->reassignHostelNoticePivot($absorbed, $survivor);

            $this->clearUniqueStudentIdentifiers($absorbed);

            if ($survivor->id === $sourceId) {
                $survivor->update(['id_number' => $normalizedIdNumber]);
            }

            $this->purgeAbsorbedAccount($absorbed);

            return $survivor->fresh(['user']);
        });
    }

    private function reassignStudentForeignKeys(Student $absorbed, Student $survivor): void
    {
        $tables = [
            'student_applications',
            'student_enrolments',
            'sponsors',
            'academic_records',
            'student_academic_results',
            'hostel_applications',
            'hostel_room_allocations',
            'hostel_queries',
            'hostel_leaves',
            'finance_transaction_queries',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->where('student_id', $absorbed->id)
                ->update(['student_id' => $survivor->id]);
        }
    }

    private function reassignMorphRelations(Student $absorbed, Student $survivor): void
    {
        $studentClass = Student::class;

        DB::table('contacts')
            ->where('contactable_type', $studentClass)
            ->where('contactable_id', $absorbed->id)
            ->update(['contactable_id' => $survivor->id]);

        DB::table('addresses')
            ->where('addressable_type', $studentClass)
            ->where('addressable_id', $absorbed->id)
            ->update(['addressable_id' => $survivor->id]);

        DB::table('next_of_kin')
            ->where('kinnable_type', $studentClass)
            ->where('kinnable_id', $absorbed->id)
            ->update(['kinnable_id' => $survivor->id]);

        DB::table('student_notes')
            ->where('noteable_type', $studentClass)
            ->where('noteable_id', $absorbed->id)
            ->update(['noteable_id' => $survivor->id]);
    }

    private function reassignUserLedgers(?User $absorbedUser, ?User $survivorUser): void
    {
        if ($absorbedUser === null || $survivorUser === null) {
            return;
        }

        Ledger::query()
            ->withTrashed()
            ->where('ledgerable_type', User::class)
            ->where('ledgerable_id', $absorbedUser->id)
            ->update(['ledgerable_id' => $survivorUser->id]);
    }

    private function reassignHostelNoticePivot(Student $absorbed, Student $survivor): void
    {
        $absorbedRows = DB::table('hostel_notice_student')
            ->where('student_id', $absorbed->id)
            ->get();

        foreach ($absorbedRows as $row) {
            $exists = DB::table('hostel_notice_student')
                ->where('hostel_notice_id', $row->hostel_notice_id)
                ->where('student_id', $survivor->id)
                ->exists();

            if ($exists) {
                DB::table('hostel_notice_student')->where('id', $row->id)->delete();

                continue;
            }

            DB::table('hostel_notice_student')
                ->where('id', $row->id)
                ->update(['student_id' => $survivor->id]);
        }
    }

    private function clearUniqueStudentIdentifiers(Student $student): void
    {
        $student->update([
            'id_number' => null,
            'student_number' => null,
            'passport_number' => null,
        ]);
    }

    private function purgeAbsorbedAccount(Student $absorbed): void
    {
        $user = $absorbed->user;

        $absorbed->forceDelete();

        if ($user === null) {
            return;
        }

        $this->userRelationPurgeService->purge($user);
        $user->forceDelete();
    }
}
