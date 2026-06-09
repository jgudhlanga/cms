<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *     source: array{student: Student, counts: array<string, int>},
 *     target: array{student: Student, counts: array<string, int>},
 *     proposedIdNumber: string
 * } $resource
 */
class StudentAccountMergePreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'proposedIdNumber' => $this->resource['proposedIdNumber'],
            'source' => $this->studentSummary(
                $this->resource['source']['student'],
                $this->resource['source']['counts'],
            ),
            'target' => $this->studentSummary(
                $this->resource['target']['student'],
                $this->resource['target']['counts'],
            ),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, mixed>
     */
    private function studentSummary(Student $student, array $counts): array
    {
        $user = $student->user;

        return [
            'studentId' => $student->id,
            'userId' => $student->user_id,
            'name' => $user?->full_name,
            'email' => $user?->email,
            'phoneNumber' => $user?->phone_number,
            'studentNumber' => $student->student_number,
            'idNumber' => $student->id_number,
            'isFaultySource' => (bool) ($counts['isFaultySource'] ?? false),
            'programmesCount' => $counts['programmesCount'] ?? 0,
            'enrolmentsCount' => $counts['enrolmentsCount'] ?? 0,
            'paidReceiptsCount' => $counts['paidReceiptsCount'] ?? 0,
            'contactsCount' => $counts['contactsCount'] ?? 0,
            'addressesCount' => $counts['addressesCount'] ?? 0,
            'academicResultsCount' => $counts['academicResultsCount'] ?? 0,
            'hostelApplicationsCount' => $counts['hostelApplicationsCount'] ?? 0,
        ];
    }
}
