<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Students\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassListDataService
{
    public const ROWS_PER_PAGE = 21;

    /**
     * @param  list<int>  $classIds
     * @return array<string, mixed>
     */
    public function assembleForClassConfig(ClassConfig $classConfig, array $classIds): array
    {
        $classConfig->loadMissing([
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentCourse.course',
            'modeOfStudy',
        ]);

        $validClassIds = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfig->id)
            ->whereIn('id', $classIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($validClassIds === []) {
            throw ValidationException::withMessages([
                'class_ids' => [__('academic_calendar.export_class_list_no_classes_selected')],
            ]);
        }

        $classes = AcademicCalendarClass::query()
            ->whereIn('id', $validClassIds)
            ->orderBy('name')
            ->get();

        $sections = $classes
            ->map(fn (AcademicCalendarClass $class): array => $this->assembleForClass($class, $classConfig))
            ->values()
            ->all();

        return [
            'institutionName' => (string) config('app.display_name'),
            'sections' => $sections,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assembleForClass(AcademicCalendarClass $class, ?ClassConfig $classConfig = null): array
    {
        $classConfig ??= $class->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            $class->loadMissing('classConfig');
            $classConfig = $class->classConfig;
        }

        abort_unless($classConfig instanceof ClassConfig, 404);

        $classConfig->loadMissing([
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentCourse.course',
            'modeOfStudy',
        ]);

        $header = $this->buildHeader($classConfig, $class);
        $studentRows = $this->fetchStudentRows($class);
        $pages = $this->paginateRows($studentRows);

        return [
            'header' => $header,
            'pages' => $pages,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function buildHeader(ClassConfig $classConfig, AcademicCalendarClass $class): array
    {
        $departmentName = $classConfig->institutionDepartment?->department?->name
            ?? $classConfig->institutionDepartment?->description
            ?? $classConfig->institutionDepartment?->department_code;

        return [
            'department' => $departmentName !== null ? (string) $departmentName : null,
            'level' => (string) ($classConfig->departmentLevel?->level?->name ?? ''),
            'program' => (string) ($classConfig->departmentCourse?->course?->name ?? ''),
            'modeOfStudy' => (string) ($classConfig->modeOfStudy?->name ?? ''),
            'academicYear' => (string) ($classConfig->calendar_year ?? ''),
            'className' => (string) $class->name,
        ];
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function fetchStudentRows(AcademicCalendarClass $class): array
    {
        return AcademicCalendarStudentEnrolment::query()
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_applications', 'student_applications.id', '=', 'student_enrolments.student_application_id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $class->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->select([
                'users.first_name',
                'users.last_name',
                'students.date_of_birth',
                'students.id_number',
                DB::raw('COALESCE(users.phone_number, (
                    SELECT phone_number FROM contacts
                    WHERE contacts.contactable_id = students.id
                    AND contacts.contactable_type = \''.addslashes(Student::class).'\'
                    AND contacts.deleted_at IS NULL
                    LIMIT 1
                )) as contact_number'),
                'students.student_number',
                'student_applications.application_tracking_number',
                'genders.title as gender_title',
            ])
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get()
            ->map(function (AcademicCalendarStudentEnrolment $row): array {
                return [
                    'surname' => (string) ($row->last_name ?? ''),
                    'firstName' => (string) ($row->first_name ?? ''),
                    'dateOfBirth' => $this->formatDateOfBirth($row->date_of_birth),
                    'nationalId' => $row->id_number !== null ? (string) $row->id_number : null,
                    'contactNumber' => $row->contact_number !== null ? (string) $row->contact_number : null,
                    'studentNumber' => $row->student_number !== null ? (string) $row->student_number : null,
                    'applicationNumber' => $row->application_tracking_number !== null ? (string) $row->application_tracking_number : null,
                    'gender' => $row->gender_title !== null ? (string) $row->gender_title : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, string|null>>  $studentRows
     * @return list<array<string, mixed>>
     */
    private function paginateRows(array $studentRows): array
    {
        $pages = [];
        $chunks = $studentRows === []
            ? [collect()]
            : collect($studentRows)->chunk(self::ROWS_PER_PAGE)->values();

        foreach ($chunks as $chunk) {
            $rows = $chunk->values()->all();
            $startNumber = count($pages) * self::ROWS_PER_PAGE + 1;
            $paddedRows = $this->padRowsToPageSize($rows, $startNumber);
            $pages[] = ['rows' => $paddedRows];
        }

        return $pages;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return list<array<string, string|int|null>>
     */
    private function padRowsToPageSize(array $rows, int $startNumber): array
    {
        $padded = [];

        for ($index = 0; $index < self::ROWS_PER_PAGE; $index++) {
            $rowNumber = $startNumber + $index;
            $student = $rows[$index] ?? null;

            $padded[] = [
                'number' => $rowNumber,
                'surname' => $student['surname'] ?? null,
                'firstName' => $student['firstName'] ?? null,
                'dateOfBirth' => $student['dateOfBirth'] ?? null,
                'nationalId' => $student['nationalId'] ?? null,
                'contactNumber' => $student['contactNumber'] ?? null,
                'studentNumber' => $student['studentNumber'] ?? null,
                'applicationNumber' => $student['applicationNumber'] ?? null,
                'gender' => $student['gender'] ?? null,
            ];
        }

        return $padded;
    }

    private function formatDateOfBirth(mixed $dateOfBirth): ?string
    {
        if ($dateOfBirth === null || $dateOfBirth === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $dateOfBirth)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }
}
