<?php

namespace App\Importers\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkMark;
use App\Rules\AcademicCalendars\ValidCourseWorkMark;
use App\Support\AcademicCalendars\CourseWorkMarkValue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\Enums\DuplicateStrategy;
use LaravelIngest\Enums\SourceType;
use LaravelIngest\IngestConfig;

class CourseWorkMarkImporter implements IngestDefinition
{
    public const string IMPORTER_NAME = 'CourseWorkMarkImporter';

    public const int FIXED_COLUMN_COUNT = 4;

    /** Zero-based row index of the column header row in the Marks sheet. */
    public const int TEMPLATE_HEADER_ROW = 5;

    public function __construct(
        private readonly int $classConfigId,
        private readonly int $moduleId,
    ) {}

    public function getConfig(): IngestConfig
    {
        return IngestConfig::for(CourseWorkMark::class)
            ->fromSource(SourceType::FILESYSTEM, [
                'disk' => 'ingest',
            ])
            ->keyedBy('STUDENT_ENROLMENT_ID')
            ->onDuplicate(DuplicateStrategy::UPDATE)
            ->validate([
                'STUDENT_ENROLMENT_ID' => ['required', 'integer'],
                'ASSESSMENT_TYPE_ID' => ['required', 'integer'],
                'MARK' => ['required', new ValidCourseWorkMark],
            ]);
    }

    public function classConfigId(): int
    {
        return $this->classConfigId;
    }

    public function moduleId(): int
    {
        return $this->moduleId;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[strtoupper(str_replace(' ', '_', (string) $key))] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<int|string, mixed>  $headerRow
     */
    public static function isWideFormatHeader(array $headerRow): bool
    {
        $values = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            array_values($headerRow),
        );

        return in_array('STUDENT_ENROLMENT_ID', $values, true)
            && ! in_array('ASSESSMENT_TYPE_ID', $values, true);
    }

    /**
     * @param  array<int|string, mixed>  $idRow
     * @return array<int, int>
     */
    public static function parseWideColumnMap(array $idRow): array
    {
        $values = array_values($idRow);
        $map = [];

        for ($index = self::FIXED_COLUMN_COUNT; $index < count($values); $index++) {
            $value = $values[$index];

            if ($value !== null && $value !== '' && is_numeric($value)) {
                $map[$index] = (int) $value;
            }
        }

        return $map;
    }

    /**
     * @param  array<int|string, mixed>  $headerRow
     * @param  array<int, int>  $columnMap
     * @return list<array{id: int, name: string, weightPercent: int|null}>
     */
    public static function assessmentColumnsFromHeader(array $headerRow, array $columnMap): array
    {
        $values = array_values($headerRow);
        $columns = [];

        foreach ($columnMap as $columnIndex => $assessmentTypeId) {
            $headerLabel = (string) ($values[$columnIndex] ?? '');
            $name = $headerLabel;
            $weightPercent = null;

            if (preg_match('/^(.+?)\s+\((\d+)%\)$/', $headerLabel, $matches) === 1) {
                $name = trim($matches[1]);
                $weightPercent = (int) $matches[2];
            }

            $columns[] = [
                'id' => $assessmentTypeId,
                'name' => $name,
                'weightPercent' => $weightPercent,
            ];
        }

        return $columns;
    }

    /**
     * @param  array<int|string, mixed>  $studentRow
     * @param  array<int, int>  $columnMap
     */
    public function isEmptyWideRow(array $studentRow, array $columnMap): bool
    {
        $values = array_values($studentRow);

        foreach ($columnMap as $columnIndex => $assessmentTypeId) {
            $mark = $values[$columnIndex] ?? null;

            if ($mark !== null && $mark !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $studentRow
     * @return array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int, mark: int, remark: null}
     */
    public function extractWideMarkPayload(array $studentRow, int $assessmentTypeId, mixed $markValue): array
    {
        $values = array_values($studentRow);
        $studentEnrolmentId = $values[0] ?? null;

        $validator = Validator::make([
            'STUDENT_ENROLMENT_ID' => $studentEnrolmentId,
            'MARK' => $markValue,
        ], [
            'STUDENT_ENROLMENT_ID' => ['required', 'integer'],
            'MARK' => ['required', new ValidCourseWorkMark],
        ], [
            'MARK.required' => __('academic_calendar.course_work_import_mark_required'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $mark = CourseWorkMarkValue::tryParse($validated['MARK']);

        if ($mark === null) {
            throw ValidationException::withMessages([
                'MARK' => [__('academic_calendar.course_work_mark_invalid')],
            ]);
        }

        return [
            'studentEnrolmentId' => (int) $validated['STUDENT_ENROLMENT_ID'],
            'courseSyllabusModuleId' => $this->moduleId,
            'assessmentTypeId' => $assessmentTypeId,
            'mark' => $mark,
            'remark' => null,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $studentRow
     * @return array{studentName: string|null, studentNumber: string|null, className: string|null}
     */
    public static function displayFromWideRow(array $studentRow): array
    {
        $values = array_values($studentRow);

        return [
            'studentName' => isset($values[2]) && $values[2] !== '' ? (string) $values[2] : null,
            'studentNumber' => isset($values[1]) && $values[1] !== '' ? (string) $values[1] : null,
            'className' => isset($values[3]) && $values[3] !== '' ? (string) $values[3] : null,
        ];
    }

    public static function markKey(int $studentEnrolmentId, int $moduleId, int $assessmentTypeId): string
    {
        return sprintf('%d:%d:%d', $studentEnrolmentId, $moduleId, $assessmentTypeId);
    }

    /**
     * @param  array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int}  $payload
     */
    public static function markKeyFromPayload(array $payload): string
    {
        return self::markKey(
            (int) $payload['studentEnrolmentId'],
            (int) $payload['courseSyllabusModuleId'],
            (int) $payload['assessmentTypeId'],
        );
    }

    /**
     * @return list<string>
     */
    public static function fixedHeaderColumns(): array
    {
        return [
            'STUDENT_ENROLMENT_ID',
            'STUDENT_NUMBER',
            'STUDENT_NAME',
            'CLASS_NAME',
        ];
    }
}
