<?php

declare(strict_types=1);

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Importers\AcademicCalendars\StudentNumberProgressionImporter;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;

class EnrolmentProgressionImportTemplateService
{
    /**
     * @return array{
     *     header: array{generatedAt: string, department: string, action: string, calendarYear: string, studentCount: int},
     *     rows: list<list<string|null>>,
     * }
     */
    public function assemble(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
        ?AcademicCalendarClass $restrictToClass = null,
    ): array {
        $department->loadMissing('department');
        $rows = $this->studentRows($department, $classConfig, $restrictToClass);

        return [
            'header' => [
                'generatedAt' => now()->toDateTimeString(),
                'department' => (string) ($department->department?->name ?? ''),
                'action' => $action->label(),
                'calendarYear' => (string) $classConfig->calendar_year,
                'studentCount' => count($rows),
            ],
            'rows' => $rows !== [] ? $rows : [array_fill(0, count($this->columns()), null)],
        ];
    }

    public function downloadFileName(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
    ): string {
        $department->loadMissing('department');
        $slug = strtolower(preg_replace('/\s+/', '-', (string) ($department->department_code ?? 'department')) ?? 'department');

        return sprintf(
            'progression-%s-%s-%s-%s.xlsx',
            $action->value,
            $slug,
            $classConfig->calendar_year,
            now()->format('Y-m-d-His'),
        );
    }

    /**
     * @return list<string>
     */
    public function instructions(EnrolmentProgressionImportAction $action): array
    {
        $base = [
            'The Students sheet is pre-filled with students currently seated on this class configuration.',
            'Keep the Student Number column — upload matching uses Student Number only.',
            'Remove rows for students who should not be processed, then upload for preview.',
            'Extra columns (ID Number, Name, Department, Level, Course, Mode) are for reference and are ignored on import.',
            'Only students found on classes for this class configuration can be processed.',
            'Ineligible rows are skipped (wrong phase, blocking status, already completed, or not found).',
        ];

        return match ($action) {
            EnrolmentProgressionImportAction::CompleteLevel => [
                ...$base,
                'Use this list for students assumed to have completed the level (last phase → Award).',
            ],
            EnrolmentProgressionImportAction::AdvancePhase => [
                ...$base,
                'Use this list for students who should continue to the next semester/phase within the same level.',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return StudentNumberProgressionImporter::COLUMNS;
    }

    /**
     * @return list<list<string|null>>
     */
    private function studentRows(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        ?AcademicCalendarClass $restrictToClass,
    ): array {
        $enrolments = StudentEnrolment::query()
            ->select('student_enrolments.*')
            ->where('student_enrolments.institution_department_id', $department->id)
            ->where('student_enrolments.department_level_id', $classConfig->department_level_id)
            ->where('student_enrolments.department_course_id', $classConfig->department_course_id)
            ->where('student_enrolments.mode_of_study_id', $classConfig->mode_of_study_id)
            ->whereNull('student_enrolments.deleted_at')
            ->whereHas('academicCalendarStudentEnrolment', function ($query) use ($classConfig, $restrictToClass): void {
                $query
                    ->whereNull('deleted_at')
                    ->where('is_live', true)
                    ->whereHas('academicCalendarClass', function ($classQuery) use ($classConfig, $restrictToClass): void {
                        $classQuery
                            ->where('class_config_id', $classConfig->id)
                            ->when(
                                $restrictToClass instanceof AcademicCalendarClass,
                                fn ($q) => $q->where('id', $restrictToClass->id),
                            );
                    });
            })
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
            ])
            ->get()
            ->sortBy(function (StudentEnrolment $enrolment): string {
                $user = $enrolment->student?->user;

                return strtolower(trim(sprintf(
                    '%s %s %s',
                    (string) ($user?->last_name ?? ''),
                    (string) ($user?->first_name ?? ''),
                    (string) ($enrolment->student?->student_number ?? ''),
                )));
            })
            ->values();

        return $enrolments
            ->map(function (StudentEnrolment $enrolment): array {
                $student = $enrolment->student;
                $user = $student?->user;
                $name = trim(sprintf('%s %s', (string) ($user?->first_name ?? ''), (string) ($user?->last_name ?? '')));
                $idNumber = (string) ($student?->id_number ?: $student?->passport_number ?: '');

                return [
                    (string) ($student?->student_number ?? ''),
                    $idNumber !== '' ? $idNumber : null,
                    $name !== '' ? $name : null,
                    (string) ($enrolment->institutionDepartment?->department?->name
                        ?? $enrolment->institutionDepartment?->department_code
                        ?? ''),
                    (string) ($enrolment->departmentLevel?->level?->name ?? ''),
                    (string) ($enrolment->departmentCourse?->course?->name ?? ''),
                    (string) ($enrolment->modeOfStudy?->name ?? ''),
                ];
            })
            ->filter(fn (array $row): bool => trim((string) ($row[0] ?? '')) !== '')
            ->values()
            ->all();
    }
}
