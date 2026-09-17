<?php

declare(strict_types=1);

namespace App\Support\Finance;

use App\Http\Requests\Finance\ExportForBillingRequest;
use Illuminate\Http\Request;

final class BillingExportFilters
{
    /**
     * @param  list<int>  $academicCalendarIds
     * @param  list<int>  $programmeSemesterIds
     * @param  list<string>  $sources
     * @param  list<string>  $syncStatuses
     */
    public function __construct(
        public readonly array $academicCalendarIds,
        public readonly ?string $studentNumberStartsWith = null,
        public readonly array $programmeSemesterIds = [],
        public readonly array $sources = [],
        public readonly array $syncStatuses = [],
        public readonly ?string $confirmedFrom = null,
        public readonly ?string $confirmedTo = null,
        public readonly ?int $institutionDepartmentId = null,
        public readonly ?int $departmentLevelId = null,
        public readonly ?int $departmentCourseId = null,
        public readonly ?int $modeOfStudyId = null,
        public readonly ?string $pastelLinked = null,
    ) {}

    public static function fromRequest(Request $request, array $defaultPeriodIds): self
    {
        $periodIds = $request->has('academic_calendar_ids')
            ? self::intList($request->input('academic_calendar_ids', []))
            : $defaultPeriodIds;

        $studentNumberStartsWith = $request->has('student_number_starts_with')
            ? trim((string) $request->input('student_number_starts_with'))
            : null;

        $pastelLinked = $request->string('pastel_linked')->toString();

        return new self(
            academicCalendarIds: $periodIds,
            studentNumberStartsWith: $studentNumberStartsWith !== null && $studentNumberStartsWith !== ''
                ? $studentNumberStartsWith
                : null,
            programmeSemesterIds: self::intList($request->input('programme_semester_ids', [])),
            sources: self::stringList($request->input('sources', [])),
            syncStatuses: self::stringList($request->input('sync_statuses', [])),
            confirmedFrom: self::nullableDate($request->input('confirmed_from')),
            confirmedTo: self::nullableDate($request->input('confirmed_to')),
            institutionDepartmentId: self::nullableInt($request->input('institution_department_id')),
            departmentLevelId: self::nullableInt($request->input('department_level_id')),
            departmentCourseId: self::nullableInt($request->input('department_course_id')),
            modeOfStudyId: self::nullableInt($request->input('mode_of_study_id')),
            pastelLinked: in_array($pastelLinked, ['linked', 'unlinked'], true) ? $pastelLinked : null,
        );
    }

    public static function fromExportRequest(ExportForBillingRequest $request): self
    {
        $studentNumberStartsWith = $request->studentNumberStartsWith();

        $pastelLinked = (string) ($request->validated('pastel_linked') ?? '');

        return new self(
            academicCalendarIds: $request->academicCalendarIds(),
            studentNumberStartsWith: $studentNumberStartsWith,
            programmeSemesterIds: $request->programmeSemesterIds(),
            sources: $request->sources(),
            syncStatuses: $request->syncStatuses(),
            confirmedFrom: self::nullableDate($request->validated('confirmed_from') ?? null),
            confirmedTo: self::nullableDate($request->validated('confirmed_to') ?? null),
            institutionDepartmentId: self::nullableInt($request->validated('institution_department_id') ?? null),
            departmentLevelId: self::nullableInt($request->validated('department_level_id') ?? null),
            departmentCourseId: self::nullableInt($request->validated('department_course_id') ?? null),
            modeOfStudyId: self::nullableInt($request->validated('mode_of_study_id') ?? null),
            pastelLinked: in_array($pastelLinked, ['linked', 'unlinked'], true) ? $pastelLinked : null,
        );
    }

    public function hasPeriods(): bool
    {
        return $this->academicCalendarIds !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'academic_calendar_ids' => $this->academicCalendarIds,
            'student_number_starts_with' => $this->studentNumberStartsWith,
            'programme_semester_ids' => $this->programmeSemesterIds,
            'sources' => $this->sources,
            'sync_statuses' => $this->syncStatuses,
            'confirmed_from' => $this->confirmedFrom,
            'confirmed_to' => $this->confirmedTo,
            'institution_department_id' => $this->institutionDepartmentId,
            'department_level_id' => $this->departmentLevelId,
            'department_course_id' => $this->departmentCourseId,
            'mode_of_study_id' => $this->modeOfStudyId,
            'pastel_linked' => $this->pastelLinked,
        ];
    }

    /**
     * @return list<int>
     */
    private static function intList(mixed $value): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', (array) $value),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), (array) $value),
            static fn (string $item): bool => $item !== '',
        ));
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private static function nullableDate(mixed $value): ?string
    {
        $date = trim((string) $value);

        return $date !== '' ? $date : null;
    }
}
