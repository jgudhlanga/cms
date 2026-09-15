<?php

namespace App\DTO\Assessments;

use App\Enums\Assessments\AssessmentWindowStatusEnum;
use Carbon\Carbon;

/**
 * The capture window that actually applies to one assessment type for a department, class and module:
 * the global calendar dates, narrowed by a department calendar, reopened by an approved extension.
 */
final readonly class EffectiveAssessmentWindow
{
    public const string SOURCE_GLOBAL = 'global';

    public const string SOURCE_DEPARTMENT = 'department';

    public function __construct(
        public ?int $assessmentTypeId,
        public string $assessmentTypeName,
        public AssessmentWindowStatusEnum $status,
        public ?string $source = null,
        public ?int $globalCalendarId = null,
        public ?int $departmentCalendarId = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $globalStartDate = null,
        public ?string $globalEndDate = null,
        public ?int $extensionId = null,
        public ?string $extendedUntil = null,
        public int $firstNotificationDaysBefore = 10,
        public int $secondNotificationDaysBefore = 5,
        public int $dueNotificationDaysBefore = 0,
    ) {}

    public static function notConfigured(?int $assessmentTypeId, string $assessmentTypeName): self
    {
        return new self($assessmentTypeId, $assessmentTypeName, AssessmentWindowStatusEnum::NotConfigured);
    }

    /**
     * One window spanning several (used for mark-only modules, which have no assessment type).
     *
     * @param  list<self>  $windows
     */
    public static function envelope(string $label, array $windows, AssessmentWindowStatusEnum $status): self
    {
        $startDates = array_values(array_filter(array_map(static fn (self $window): ?string => $window->startDate, $windows)));
        $endDates = array_values(array_filter(array_map(static fn (self $window): ?string => $window->endDate, $windows)));
        $globalEndDates = array_values(array_filter(array_map(static fn (self $window): ?string => $window->globalEndDate, $windows)));

        return new self(
            assessmentTypeId: null,
            assessmentTypeName: $label,
            status: $status,
            startDate: $startDates === [] ? null : min($startDates),
            endDate: $endDates === [] ? null : max($endDates),
            globalEndDate: $globalEndDates === [] ? null : max($globalEndDates),
        );
    }

    public function withExtension(int $extensionId, string $extendedUntil): self
    {
        return new self(
            assessmentTypeId: $this->assessmentTypeId,
            assessmentTypeName: $this->assessmentTypeName,
            status: AssessmentWindowStatusEnum::Extended,
            source: $this->source,
            globalCalendarId: $this->globalCalendarId,
            departmentCalendarId: $this->departmentCalendarId,
            startDate: $this->startDate,
            endDate: $this->endDate,
            globalStartDate: $this->globalStartDate,
            globalEndDate: $this->globalEndDate,
            extensionId: $extensionId,
            extendedUntil: $extendedUntil,
            firstNotificationDaysBefore: $this->firstNotificationDaysBefore,
            secondNotificationDaysBefore: $this->secondNotificationDaysBefore,
            dueNotificationDaysBefore: $this->dueNotificationDaysBefore,
        );
    }

    public function isCaptureAllowed(): bool
    {
        return match ($this->status) {
            AssessmentWindowStatusEnum::Open, AssessmentWindowStatusEnum::Extended => true,
            AssessmentWindowStatusEnum::NotConfigured => ! (bool) config('coursework.require_assessment_calendar', true),
            default => false,
        };
    }

    public function isClosed(): bool
    {
        return $this->status === AssessmentWindowStatusEnum::Closed;
    }

    public function message(): string
    {
        $replace = [
            'assessment' => $this->assessmentTypeName,
            'start_date' => self::displayDate($this->startDate),
            'end_date' => self::displayDate($this->endDate),
            'extended_until' => self::displayDate($this->extendedUntil),
        ];

        return match ($this->status) {
            AssessmentWindowStatusEnum::NotConfigured => __('academic_calendar.course_work_window_message_not_configured', $replace),
            AssessmentWindowStatusEnum::NotOpen => __('academic_calendar.course_work_window_message_not_open', $replace),
            AssessmentWindowStatusEnum::Open => $this->source === self::SOURCE_DEPARTMENT
                ? __('academic_calendar.course_work_window_message_open_department', $replace)
                : __('academic_calendar.course_work_window_message_open', $replace),
            AssessmentWindowStatusEnum::Extended => __('academic_calendar.course_work_window_message_extended', $replace),
            AssessmentWindowStatusEnum::Closed => __('academic_calendar.course_work_window_message_closed', $replace),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'assessmentTypeId' => $this->assessmentTypeId,
            'assessmentTypeName' => $this->assessmentTypeName,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'captureAllowed' => $this->isCaptureAllowed(),
            'source' => $this->source,
            'globalCalendarId' => $this->globalCalendarId,
            'departmentCalendarId' => $this->departmentCalendarId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'globalStartDate' => $this->globalStartDate,
            'globalEndDate' => $this->globalEndDate,
            'extensionId' => $this->extensionId,
            'extendedUntil' => $this->extendedUntil,
            'message' => $this->message(),
        ];
    }

    private static function displayDate(?string $date): string
    {
        return $date === null ? '' : Carbon::parse($date)->format('d M Y');
    }
}
