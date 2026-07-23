<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Exceptions\Maintenance\StudentIdNumberConflictException;
use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Throwable;

class FixStudentIdNumberService
{
    public function __construct(
        private readonly EnrollmentLookupService $enrollmentLookup,
        private readonly FaultyStudentIdNumberAnalysis $analysis,
    ) {}

    public function fix(Student $student, string $idNumber): Student
    {
        if ($student->id_number === null || ZimbabweanIdNumber::isValid($student->id_number)) {
            throw ValidationException::withMessages([
                'id_number' => [__('trans.maintenance_faulty_data_student_not_faulty')],
            ]);
        }

        $normalized = EnrollmentLookupService::normalizeNationalId($idNumber);
        $conflict = $this->enrollmentLookup->findStudentByNationalIdUnscoped($normalized, $student->id);

        if ($conflict !== null) {
            throw new StudentIdNumberConflictException($conflict->id, $normalized);
        }

        try {
            $student->update([
                'id_number' => $normalized,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $conflict = $this->enrollmentLookup->findStudentByNationalIdUnscoped($normalized, $student->id);

            if ($conflict !== null) {
                throw new StudentIdNumberConflictException($conflict->id, $normalized);
            }

            throw $exception;
        }

        return $student->fresh(['user']);
    }

    /**
     * @param  list<int>  $studentIds
     * @return array{
     *     fixed_ids: list<int>,
     *     failed: list<array{id: int, message: string}>
     * }
     */
    public function fixMany(array $studentIds): array
    {
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $fixedIds = [];
        $failed = [];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);

            if ($student === null) {
                $failed[] = [
                    'id' => $studentId,
                    'message' => __('trans.maintenance_faulty_data_bulk_fix_not_found'),
                ];

                continue;
            }

            $analysis = $this->analysis->analyze($student);

            if (
                $analysis['rectificationStatus'] !== FaultyStudentIdNumberAnalysis::STATUS_READY_TO_FIX
                || $analysis['proposedIdNumber'] === null
            ) {
                $failed[] = [
                    'id' => $studentId,
                    'message' => __('trans.maintenance_faulty_data_bulk_fix_not_ready'),
                ];

                continue;
            }

            try {
                $this->fix($student, $analysis['proposedIdNumber']);
                $fixedIds[] = $studentId;
            } catch (StudentIdNumberConflictException $exception) {
                $failed[] = [
                    'id' => $studentId,
                    'message' => $exception->getMessage(),
                ];
            } catch (ValidationException $exception) {
                $messages = $exception->errors()['id_number'] ?? [];
                $failed[] = [
                    'id' => $studentId,
                    'message' => $messages[0] ?? __('trans.maintenance_faulty_data_fix_failure'),
                ];
            } catch (Throwable) {
                $failed[] = [
                    'id' => $studentId,
                    'message' => __('trans.maintenance_faulty_data_fix_failure'),
                ];
            }
        }

        return [
            'fixed_ids' => $fixedIds,
            'failed' => $failed,
        ];
    }
}
