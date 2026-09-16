<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
use App\Services\Students\StudyPosition\StudyPositionReportService;
use Illuminate\Support\Collection;

class StudentListExportService
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'Name',
        'ID Number',
        'Student Number',
        'Gender',
        'Department',
        'Level',
        'Course',
        'Mode of Study',
        'Study Period',
        'Study Position Status',
        'Confirmed Phase',
        'Phase on Record',
        'Confirmed Via',
        'Confirmed At',
        'Review Note',
    ];

    public function __construct(
        protected IStudentRepository $repository,
        protected StudyPositionReportService $studyPositionReport,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return list<list<string|null>>
     */
    public function rows(array $filters): array
    {
        $rows = [self::HEADERS];
        $exportedIds = [];

        $this->repository
            ->queryForExport($filters)
            ->chunkById(200, function (Collection $students) use (&$rows, &$exportedIds): void {
                $studyPositions = $this->studyPositionReport->summariesForStudentIds(
                    $students->pluck('id')->map(fn (mixed $id): int => (int) $id)->unique()->values()->all(),
                );

                foreach ($students as $student) {
                    /** @var Student $student */
                    $id = (int) $student->id;
                    if (isset($exportedIds[$id])) {
                        continue;
                    }
                    $exportedIds[$id] = true;
                    $rows[] = $this->mapRow($student, $studyPositions[$id] ?? null);
                }
            }, 'students.id', 'id');

        return $rows;
    }

    /**
     * @param  array{period: string, state: string, confirmedPhase: string|null, phaseOnRecord: string|null, source: string|null, confirmedAt: string|null, note: string|null}|null  $studyPosition
     * @return list<string|null>
     */
    private function mapRow(Student $student, ?array $studyPosition): array
    {
        $enrolment = $student->latestEnrolment;

        return [
            $student->user?->full_name,
            $student->id_number,
            $student->student_number,
            $student->gender?->title,
            $enrolment?->institutionDepartment?->department?->name,
            $enrolment?->departmentLevel?->level?->name,
            $enrolment?->departmentCourse?->course?->name,
            $enrolment?->modeOfStudy?->name,
            $studyPosition['period'] ?? null,
            $studyPosition['state'] ?? null,
            $studyPosition['confirmedPhase'] ?? null,
            $studyPosition['phaseOnRecord'] ?? null,
            $studyPosition['source'] ?? null,
            $studyPosition['confirmedAt'] ?? null,
            $studyPosition['note'] ?? null,
        ];
    }
}
