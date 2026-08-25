<?php

namespace App\DTO\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Requests\Institution\AssessmentCalendarRequest;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;

readonly class AssessmentCalendarDto
{
    public function __construct(
        public int $assessment_type_id,
        public int $academic_calendar_id,
        public string $start_date,
        public string $end_date,
        public int $first_notification_days_before,
        public int $second_notification_days_before,
        public int $due_notification_days_before,
        public AcademicCalendarTypeEnum $type,
    ) {}

    public static function fromAssessmentCalendarRequest(
        AssessmentCalendarRequest $request,
        AssessmentType $assessmentType,
    ): self {
        return new self(
            assessment_type_id: $assessmentType->id,
            academic_calendar_id: (int) $request->academic_calendar_id,
            start_date: $request->start_date,
            end_date: $request->end_date,
            first_notification_days_before: (int) ($request->input(
                'first_notification_days_before',
                AssessmentCalendar::DEFAULT_FIRST_NOTIFICATION_DAYS,
            )),
            second_notification_days_before: (int) ($request->input(
                'second_notification_days_before',
                AssessmentCalendar::DEFAULT_SECOND_NOTIFICATION_DAYS,
            )),
            due_notification_days_before: (int) ($request->input(
                'due_notification_days_before',
                AssessmentCalendar::DEFAULT_DUE_NOTIFICATION_DAYS,
            )),
            type: AcademicCalendarTypeEnum::from($request->type),
        );
    }
}
