<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property array{
 *     source: array{student: Student, programs: Collection<int, StudentApplication>, counts: array<string, int>},
 *     target: array{student: Student, programs: Collection<int, StudentApplication>, counts: array<string, int>},
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
                $this->resource['source']['programs'],
            ),
            'target' => $this->studentSummary(
                $this->resource['target']['student'],
                $this->resource['target']['counts'],
                $this->resource['target']['programs'],
            ),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @param  Collection<int, StudentApplication>  $programs
     * @return array<string, mixed>
     */
    private function studentSummary(Student $student, array $counts, Collection $programs): array
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
            'applications' => $programs
                ->map(fn (StudentApplication $program): array => $this->applicationRow($program))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationRow(StudentApplication $program): array
    {
        $classListType = $program->classList?->type;
        $resolvedClassListType = $classListType instanceof ClassListTypeEnum
            ? $classListType->value
            : (is_string($classListType) ? $classListType : null);

        $workflowSlug = $program->departmentWorkflowStep?->workflowStep?->slug;

        return [
            'id' => $program->id,
            'departmentCode' => $program->institutionDepartment?->department_code,
            'level' => $program->departmentLevel?->level?->name,
            'course' => $program->departmentCourse?->course?->name,
            'intakePeriod' => $program->intakePeriod?->name,
            'modeOfStudy' => $program->modeOfStudy?->name,
            'applicationStatus' => $program->departmentWorkflowStep?->workflowStep?->name,
            'classListType' => $resolvedClassListType,
            'canReject' => ! in_array($workflowSlug, [
                WorkflowStepEnum::REJECTED->slug(),
                WorkflowStepEnum::ENROLLED->slug(),
            ], true),
        ];
    }
}
