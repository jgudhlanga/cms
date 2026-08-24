<?php

declare(strict_types=1);

namespace App\Queries\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
use Illuminate\Support\Collection;

class UnassignedClassConfigStudentsQuery
{
    public function __construct(
        private readonly ConfirmedStudentsQuery $confirmedStudentsQuery,
    ) {}

    /**
     * @return list<array{studentEnrolmentId: int, studentId: int, applicationTrackingNumber: mixed, studentNumber: mixed, gender: mixed, name: string}>
     */
    public function list(InstitutionDepartment $institutionDepartment, ClassConfig $classConfig, string $calendarYear): array
    {
        return $this->eligibleRows($institutionDepartment, $classConfig, $calendarYear)
            ->map(fn (object $row): array => $this->mapRow($row))
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $studentEnrolmentIds
     * @return Collection<int, object>
     */
    public function eligibleRowsForEnrolmentIds(
        InstitutionDepartment $institutionDepartment,
        ClassConfig $classConfig,
        string $calendarYear,
        array $studentEnrolmentIds,
    ): Collection {
        $wanted = array_fill_keys($studentEnrolmentIds, true);

        return $this->eligibleRows($institutionDepartment, $classConfig, $calendarYear)
            ->filter(fn (object $row): bool => isset($wanted[(int) $row->student_enrolment_id]));
    }

    /**
     * @return Collection<int, object>
     */
    private function eligibleRows(InstitutionDepartment $institutionDepartment, ClassConfig $classConfig, string $calendarYear): Collection
    {
        $calendarIds = AcademicCalendar::idsForStartedCalendarYear($calendarYear);
        $semesterId = $classConfig->semester_id !== null ? (int) $classConfig->semester_id : null;

        $finalStudents = $this->confirmedStudentsQuery->listForClassAllocation(
            (int) $institutionDepartment->id,
            (int) $classConfig->department_level_id,
            (int) $classConfig->department_course_id,
            (int) $classConfig->mode_of_study_id,
            $calendarIds,
            $semesterId,
        );

        $assignedSemesterIds = AcademicCalendarStudentEnrolment::query()
            ->join('academic_calendar_classes', 'academic_calendar_classes.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->where('academic_calendar_classes.class_config_id', $classConfig->id)
            ->whereNotNull('academic_calendar_student_enrolments.student_semesters_id')
            ->pluck('academic_calendar_student_enrolments.student_semesters_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->flip()
            ->all();

        if ($assignedSemesterIds === []) {
            return $finalStudents->values();
        }

        return $finalStudents
            ->reject(fn (object $student): bool => isset($assignedSemesterIds[(int) $student->student_semesters_id]))
            ->values();
    }

    /**
     * @return array{studentEnrolmentId: int, studentId: int, applicationTrackingNumber: mixed, studentNumber: mixed, gender: mixed, name: string}
     */
    private function mapRow(object $row): array
    {
        return [
            'studentEnrolmentId' => (int) $row->student_enrolment_id,
            'studentId' => (int) ($row->user_id ?? $row->student_id),
            'applicationTrackingNumber' => $row->application_tracking_number,
            'studentNumber' => $row->student_number ?: $row->application_tracking_number,
            'gender' => $row->gender_title,
            'name' => trim(sprintf('%s %s', (string) ($row->first_name ?? ''), (string) ($row->last_name ?? ''))),
        ];
    }
}
