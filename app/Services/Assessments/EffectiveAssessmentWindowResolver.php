<?php

namespace App\Services\Assessments;

use App\DTO\Assessments\EffectiveAssessmentWindow;
use App\Enums\Assessments\AssessmentWindowStatusEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Students\StudentEnrolment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "is coursework capture open?". Every consumer (mark writes, lock payloads,
 * window lists, templates, imports, notifications) resolves windows here so they always agree.
 *
 * Resolution: global calendar for the academic calendar and mode of study → department calendar
 * replaces its dates → an approved, unexpired extension reopens a closed window for one class/module.
 */
class EffectiveAssessmentWindowResolver
{
    /** @var array<int, Collection<int, AssessmentCalendar>> */
    private array $globalCalendarsByAcademicCalendar = [];

    /** @var array<string, Collection<int, DepartmentAssessmentCalendar>> */
    private array $departmentCalendarsByKey = [];

    /** @var array<string, Collection<int, CourseWorkCaptureExtension>> */
    private array $extensionsByKey = [];

    /**
     * @return array<int, EffectiveAssessmentWindow> Keyed by assessment type id.
     */
    public function windowsFor(
        int $academicCalendarId,
        int $institutionDepartmentId,
        int $modeOfStudyId,
        ?int $classId = null,
        ?int $moduleId = null,
        ?CarbonInterface $today = null,
    ): array {
        $day = $this->day($today);
        $windows = [];

        foreach ($this->globalCalendarsForMode($academicCalendarId, $modeOfStudyId) as $assessmentTypeId => $globalCalendar) {
            $windows[$assessmentTypeId] = $this->buildWindow(
                $globalCalendar,
                $institutionDepartmentId,
                $classId,
                $moduleId,
                $day,
            );
        }

        return $windows;
    }

    public function windowFor(
        int $academicCalendarId,
        int $institutionDepartmentId,
        int $modeOfStudyId,
        int $assessmentTypeId,
        ?int $classId = null,
        ?int $moduleId = null,
        ?CarbonInterface $today = null,
    ): EffectiveAssessmentWindow {
        $windows = $this->windowsFor($academicCalendarId, $institutionDepartmentId, $modeOfStudyId, $classId, $moduleId, $today);

        return $windows[$assessmentTypeId] ?? EffectiveAssessmentWindow::notConfigured(
            $assessmentTypeId,
            (string) (AssessmentType::query()->whereKey($assessmentTypeId)->value('name') ?? ''),
        );
    }

    /**
     * Mark-only modules have no assessment type: capture is open while any applicable window is open,
     * not yet open while a window is still to come, otherwise closed (unless an extension reopens it).
     */
    public function moduleMarkWindowFor(
        int $academicCalendarId,
        int $institutionDepartmentId,
        int $modeOfStudyId,
        ?int $classId = null,
        ?int $moduleId = null,
        ?CarbonInterface $today = null,
    ): EffectiveAssessmentWindow {
        $day = $this->day($today);
        $label = __('academic_calendar.course_work_window_module_mark');
        $windows = array_values($this->windowsFor($academicCalendarId, $institutionDepartmentId, $modeOfStudyId, null, null, $day));

        if ($windows === []) {
            return EffectiveAssessmentWindow::notConfigured(null, $label);
        }

        $open = array_values(array_filter(
            $windows,
            static fn (EffectiveAssessmentWindow $window): bool => $window->status === AssessmentWindowStatusEnum::Open,
        ));

        if ($open !== []) {
            return EffectiveAssessmentWindow::envelope($label, $open, AssessmentWindowStatusEnum::Open);
        }

        $upcoming = array_values(array_filter(
            $windows,
            static fn (EffectiveAssessmentWindow $window): bool => $window->status === AssessmentWindowStatusEnum::NotOpen,
        ));

        if ($upcoming !== []) {
            return EffectiveAssessmentWindow::envelope($label, $upcoming, AssessmentWindowStatusEnum::NotOpen);
        }

        $closed = EffectiveAssessmentWindow::envelope($label, $windows, AssessmentWindowStatusEnum::Closed);
        $extension = $this->activeExtension($classId, $moduleId, null, $day);

        return $extension instanceof CourseWorkCaptureExtension
            ? $closed->withExtension((int) $extension->id, $extension->approved_until->toDateString())
            : $closed;
    }

    /**
     * The academic calendar a class sits in, taken from its students' enrolments (most common value).
     */
    public function academicCalendarIdForClass(int $classId): ?int
    {
        return $this->mostCommonAcademicCalendarId(
            fn (Builder $query): Builder => $query->where('aces.academic_calendar_class_id', $classId),
        );
    }

    public function academicCalendarIdForClassConfig(int $classConfigId): ?int
    {
        return $this->mostCommonAcademicCalendarId(
            fn (Builder $query): Builder => $query
                ->join('academic_calendar_classes as acc', 'acc.id', '=', 'aces.academic_calendar_class_id')
                ->where('acc.class_config_id', $classConfigId)
                ->whereNull('acc.deleted_at'),
        );
    }

    public function academicCalendarIdForEnrolment(int $studentEnrolmentId): ?int
    {
        $academicCalendarId = StudentEnrolment::query()->whereKey($studentEnrolmentId)->value('academic_calendar_id');

        return $academicCalendarId !== null ? (int) $academicCalendarId : null;
    }

    public function flush(): void
    {
        $this->globalCalendarsByAcademicCalendar = [];
        $this->departmentCalendarsByKey = [];
        $this->extensionsByKey = [];
    }

    private function buildWindow(
        AssessmentCalendar $globalCalendar,
        int $institutionDepartmentId,
        ?int $classId,
        ?int $moduleId,
        Carbon $day,
    ): EffectiveAssessmentWindow {
        $departmentCalendar = $this
            ->departmentCalendarsFor((int) $globalCalendar->academic_calendar_id, $institutionDepartmentId)
            ->get((int) $globalCalendar->id);

        $globalStart = Carbon::parse($globalCalendar->start_date)->startOfDay();
        $globalEnd = Carbon::parse($globalCalendar->end_date)->startOfDay();
        $start = $departmentCalendar instanceof DepartmentAssessmentCalendar
            ? Carbon::parse($departmentCalendar->start_date)->startOfDay()
            : $globalStart;
        $end = $departmentCalendar instanceof DepartmentAssessmentCalendar
            ? Carbon::parse($departmentCalendar->end_date)->startOfDay()
            : $globalEnd;

        $status = match (true) {
            $day->lt($start) => AssessmentWindowStatusEnum::NotOpen,
            $day->lte($end) => AssessmentWindowStatusEnum::Open,
            default => AssessmentWindowStatusEnum::Closed,
        };

        $daysSource = $departmentCalendar instanceof DepartmentAssessmentCalendar ? $departmentCalendar : $globalCalendar;

        if ($departmentCalendar instanceof DepartmentAssessmentCalendar) {
            $departmentCalendar->setRelation('assessmentCalendar', $globalCalendar);
        }

        $window = new EffectiveAssessmentWindow(
            assessmentTypeId: (int) $globalCalendar->assessment_type_id,
            assessmentTypeName: (string) ($globalCalendar->assessmentType?->name ?? ''),
            status: $status,
            source: $departmentCalendar instanceof DepartmentAssessmentCalendar
                ? EffectiveAssessmentWindow::SOURCE_DEPARTMENT
                : EffectiveAssessmentWindow::SOURCE_GLOBAL,
            globalCalendarId: (int) $globalCalendar->id,
            departmentCalendarId: $departmentCalendar?->id !== null ? (int) $departmentCalendar->id : null,
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            globalStartDate: $globalStart->toDateString(),
            globalEndDate: $globalEnd->toDateString(),
            firstNotificationDaysBefore: $daysSource->daysBeforeFor(MissingMarksNotificationTierEnum::First),
            secondNotificationDaysBefore: $daysSource->daysBeforeFor(MissingMarksNotificationTierEnum::Second),
            dueNotificationDaysBefore: $daysSource->daysBeforeFor(MissingMarksNotificationTierEnum::Due),
        );

        if ($status !== AssessmentWindowStatusEnum::Closed) {
            return $window;
        }

        $extension = $this->activeExtension($classId, $moduleId, (int) $globalCalendar->assessment_type_id, $day);

        return $extension instanceof CourseWorkCaptureExtension
            ? $window->withExtension((int) $extension->id, $extension->approved_until->toDateString())
            : $window;
    }

    /**
     * @return Collection<int, AssessmentCalendar> Keyed by assessment type id (latest end date wins).
     */
    private function globalCalendarsForMode(int $academicCalendarId, int $modeOfStudyId): Collection
    {
        if ($academicCalendarId < 1 || $modeOfStudyId < 1) {
            return collect();
        }

        $this->globalCalendarsByAcademicCalendar[$academicCalendarId] ??= AssessmentCalendar::query()
            ->where('academic_calendar_id', $academicCalendarId)
            ->with('assessmentType')
            ->orderBy('end_date')
            ->get();

        return $this->globalCalendarsByAcademicCalendar[$academicCalendarId]
            ->filter(function (AssessmentCalendar $calendar) use ($modeOfStudyId): bool {
                $assessmentType = $calendar->assessmentType;

                return $assessmentType instanceof AssessmentType
                    && in_array($modeOfStudyId, array_map('intval', $assessmentType->modes_of_study ?? []), true);
            })
            ->keyBy(fn (AssessmentCalendar $calendar): int => (int) $calendar->assessment_type_id);
    }

    /**
     * @return Collection<int, DepartmentAssessmentCalendar> Keyed by global assessment calendar id.
     */
    private function departmentCalendarsFor(int $academicCalendarId, int $institutionDepartmentId): Collection
    {
        if ($institutionDepartmentId < 1) {
            return collect();
        }

        $key = $academicCalendarId.'-'.$institutionDepartmentId;

        return $this->departmentCalendarsByKey[$key] ??= DepartmentAssessmentCalendar::query()
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('assessmentCalendar', fn (Builder $query): Builder => $query->where('academic_calendar_id', $academicCalendarId))
            ->get()
            ->keyBy(fn (DepartmentAssessmentCalendar $calendar): int => (int) $calendar->assessment_calendar_id);
    }

    private function activeExtension(?int $classId, ?int $moduleId, ?int $assessmentTypeId, Carbon $day): ?CourseWorkCaptureExtension
    {
        if ($classId === null || $moduleId === null) {
            return null;
        }

        $key = $classId.'-'.$moduleId.'-'.$day->toDateString();

        $this->extensionsByKey[$key] ??= CourseWorkCaptureExtension::query()
            ->where('academic_calendar_class_id', $classId)
            ->where('course_syllabus_module_id', $moduleId)
            ->activeOn($day)
            ->orderByDesc('approved_until')
            ->get();

        return $this->extensionsByKey[$key]->first(
            fn (CourseWorkCaptureExtension $extension): bool => $assessmentTypeId === null
                ? $extension->assessment_type_id === null
                : (int) $extension->assessment_type_id === $assessmentTypeId,
        );
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function mostCommonAcademicCalendarId(callable $scope): ?int
    {
        $query = StudentEnrolment::query()
            ->join('academic_calendar_student_enrolments as aces', 'aces.student_enrolment_id', '=', 'student_enrolments.id')
            ->whereNull('aces.deleted_at')
            ->where(fn (Builder $live): Builder => $live->where('aces.is_live', true)->orWhereNull('aces.is_live'))
            ->whereNotNull('student_enrolments.academic_calendar_id');

        $row = $scope($query)
            ->selectRaw('student_enrolments.academic_calendar_id as resolved_academic_calendar_id, count(*) as enrolment_count')
            ->groupBy('student_enrolments.academic_calendar_id')
            ->orderByDesc('enrolment_count')
            ->orderByDesc('student_enrolments.academic_calendar_id')
            ->first();

        return $row !== null ? (int) $row->resolved_academic_calendar_id : null;
    }

    private function day(?CarbonInterface $today): Carbon
    {
        return Carbon::parse($today ?? now())->startOfDay();
    }
}
