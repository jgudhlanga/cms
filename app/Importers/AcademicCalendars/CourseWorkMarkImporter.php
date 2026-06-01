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
                'MODULE_ID' => ['required', 'integer'],
                'ASSESSMENT_TYPE_ID' => ['required', 'integer'],
                'MARK' => ['required', new ValidCourseWorkMark],
                'REMARK' => ['nullable', 'string', 'max:2000'],
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
     * @param  array<string, mixed>  $row
     * @return array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int, mark: int|null, remark: string|null}
     */
    public function extractPayload(array $row): array
    {
        $normalized = $this->normalizeRow($row);

        $validator = Validator::make($normalized, [
            'STUDENT_ENROLMENT_ID' => ['required', 'integer'],
            'MODULE_ID' => ['required', 'integer'],
            'ASSESSMENT_TYPE_ID' => ['required', 'integer'],
            'MARK' => ['required', new ValidCourseWorkMark],
            'REMARK' => ['nullable', 'string', 'max:2000'],
        ], [
            'MARK.required' => __('academic_calendar.course_work_import_mark_required'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if ((int) $validated['MODULE_ID'] !== $this->moduleId) {
            throw ValidationException::withMessages([
                'MODULE_ID' => [__('academic_calendar.course_work_import_module_mismatch')],
            ]);
        }

        $mark = CourseWorkMarkValue::tryParse($validated['MARK']);

        if ($mark === null) {
            throw ValidationException::withMessages([
                'MARK' => [__('academic_calendar.course_work_mark_invalid')],
            ]);
        }

        $remark = array_key_exists('REMARK', $validated) && $validated['REMARK'] !== null && trim((string) $validated['REMARK']) !== ''
            ? trim((string) $validated['REMARK'])
            : null;

        return [
            'studentEnrolmentId' => (int) $validated['STUDENT_ENROLMENT_ID'],
            'courseSyllabusModuleId' => (int) $validated['MODULE_ID'],
            'assessmentTypeId' => (int) $validated['ASSESSMENT_TYPE_ID'],
            'mark' => $mark,
            'remark' => $remark,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function isEmptyRow(array $row): bool
    {
        $normalized = $this->normalizeRow($row);

        $mark = $normalized['MARK'] ?? null;
        $remark = $normalized['REMARK'] ?? null;

        $markEmpty = $mark === null || $mark === '';
        $remarkEmpty = $remark === null || trim((string) $remark) === '';

        return $markEmpty && $remarkEmpty;
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
    public static function headerColumns(): array
    {
        return [
            'STUDENT_ENROLMENT_ID',
            'STUDENT_NUMBER',
            'STUDENT_NAME',
            'CLASS_NAME',
            'MODULE_ID',
            'MODULE_CODE',
            'MODULE_TITLE',
            'ASSESSMENT_TYPE_ID',
            'ASSESSMENT_NAME',
            'WEIGHT_PERCENT',
            'MARK',
            'REMARK',
        ];
    }
}
