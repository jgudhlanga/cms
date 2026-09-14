<?php

namespace App\Enums\Assessments;

enum AssessmentWindowStatusEnum: string
{
    case NotConfigured = 'not_configured';
    case NotOpen = 'not_open';
    case Open = 'open';
    case Extended = 'extended';
    case Closed = 'closed';

    public function label(): string
    {
        return __('academic_calendar.course_work_window_status_'.$this->value);
    }
}
