<?php

namespace App\DTO\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Requests\Institution\AssessmentCalendarRequest;
use App\Models\Institution\AssessmentType;

readonly class AssessmentCalendarDto
{
    public function __construct(
        public int $assessment_type_id,
        public int $academic_calendar_id,
        public string $start_date,
        public string $end_date,
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
            type: AcademicCalendarTypeEnum::from($request->type),
        );
    }
}
