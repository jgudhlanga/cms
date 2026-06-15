<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FaultyStudentIdNumberAnalysis
{
    public const STATUS_DUPLICATE_MERGE = 'duplicate_merge';

    public const STATUS_READY_TO_FIX = 'ready_to_fix';

    public const STATUS_MANUAL_CORRECTION = 'manual_correction';

    public function __construct(
        private readonly EnrollmentLookupService $enrollmentLookup,
    ) {}

    /**
     * @param  Collection<int, Student>  $faultyStudents
     * @return array<string, Student>
     */
    public function buildConflictOwnerMap(Collection $faultyStudents): array
    {
        $proposedCompacts = $faultyStudents
            ->map(fn (Student $student): ?string => $this->proposedIdNumberFor((string) $student->id_number))
            ->filter()
            ->map(fn (string $proposedIdNumber): string => $this->compactId($proposedIdNumber))
            ->unique()
            ->values()
            ->all();

        if ($proposedCompacts === []) {
            return [];
        }

        $owners = Student::query()
            ->withTrashed()
            ->with('user')
            ->whereNotNull('id_number')
            ->where(function (Builder $query) use ($proposedCompacts): void {
                foreach ($proposedCompacts as $compact) {
                    $query->orWhereRaw('UPPER(TRIM(REPLACE(id_number, "-", ""))) = ?', [$compact]);
                }
            })
            ->get();

        $map = [];

        foreach ($owners as $owner) {
            $compact = $this->compactId((string) $owner->id_number);
            $existing = $map[$compact] ?? null;

            if ($existing === null) {
                $map[$compact] = $owner;

                continue;
            }

            if (! ZimbabweanIdNumber::isValid((string) $existing->id_number) && ZimbabweanIdNumber::isValid((string) $owner->id_number)) {
                $map[$compact] = $owner;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, Student>  $ownerMap
     * @return array{
     *     proposedIdNumber: string|null,
     *     suggestedIdNumber: string|null,
     *     rectificationStatus: string,
     *     rectificationPriority: int,
     *     conflict: array<string, mixed>|null
     * }
     */
    public function analyze(Student $student, array $ownerMap = []): array
    {
        $currentIdNumber = (string) $student->id_number;
        $proposedIdNumber = $this->proposedIdNumberFor($currentIdNumber);
        $suggestedIdNumber = $proposedIdNumber;

        $conflict = null;
        $rectificationStatus = self::STATUS_MANUAL_CORRECTION;

        if ($proposedIdNumber !== null) {
            $conflictingStudent = $this->resolveConflictingStudent(
                $student->id,
                $proposedIdNumber,
                $ownerMap,
            );

            if ($conflictingStudent !== null) {
                $rectificationStatus = self::STATUS_DUPLICATE_MERGE;
                $conflict = $this->conflictPayload($student, $conflictingStudent, $proposedIdNumber);
            } else {
                $rectificationStatus = self::STATUS_READY_TO_FIX;
            }
        }

        return [
            'proposedIdNumber' => $proposedIdNumber,
            'suggestedIdNumber' => $suggestedIdNumber,
            'rectificationStatus' => $rectificationStatus,
            'rectificationPriority' => $this->priorityForStatus($rectificationStatus),
            'conflict' => $conflict,
        ];
    }

    private function proposedIdNumberFor(string $currentIdNumber): ?string
    {
        $normalized = EnrollmentLookupService::normalizeNationalId($currentIdNumber);

        if (! ZimbabweanIdNumber::isValid($normalized) || $normalized === strtoupper(trim($currentIdNumber))) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<string, Student>  $ownerMap
     */
    private function resolveConflictingStudent(int $studentId, string $proposedIdNumber, array $ownerMap): ?Student
    {
        if ($ownerMap !== []) {
            $owner = $ownerMap[$this->compactId($proposedIdNumber)] ?? null;

            if ($owner !== null && $owner->id !== $studentId) {
                return $owner;
            }

            if ($owner !== null) {
                return null;
            }
        }

        $conflictingStudent = $this->enrollmentLookup->findStudentByNationalIdUnscoped(
            $proposedIdNumber,
            $studentId,
        );

        return $conflictingStudent;
    }

    private function compactId(string $idNumber): string
    {
        return strtoupper(str_replace('-', '', EnrollmentLookupService::normalizeNationalId($idNumber)));
    }

    /**
     * @return array<string, mixed>
     */
    private function conflictPayload(Student $source, Student $conflictingStudent, string $idNumber): array
    {
        $conflictingUser = $conflictingStudent->user;

        return [
            'conflictingStudentId' => $conflictingStudent->id,
            'conflictingStudentName' => $conflictingUser?->full_name,
            'conflictingStudentNumber' => $conflictingStudent->student_number,
            'conflictingPhoneNumber' => $conflictingUser?->phone_number,
            'idNumber' => $idNumber,
            'mergePreviewUrl' => route('maintenance.faulty-student-ids.merge', [
                'student' => $source->id,
                'target' => $conflictingStudent->id,
                'id_number' => $idNumber,
            ]),
        ];
    }

    private function priorityForStatus(string $status): int
    {
        return match ($status) {
            self::STATUS_DUPLICATE_MERGE => 0,
            self::STATUS_READY_TO_FIX => 1,
            default => 2,
        };
    }
}
