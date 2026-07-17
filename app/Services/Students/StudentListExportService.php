<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
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
    ];

    public function __construct(
        protected IStudentRepository $repository,
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
                foreach ($students as $student) {
                    /** @var Student $student */
                    $id = (int) $student->id;
                    if (isset($exportedIds[$id])) {
                        continue;
                    }
                    $exportedIds[$id] = true;
                    $rows[] = $this->mapRow($student);
                }
            }, 'students.id', 'id');

        return $rows;
    }

    /**
     * @return list<string|null>
     */
    private function mapRow(Student $student): array
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
        ];
    }
}
