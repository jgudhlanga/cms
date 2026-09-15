<?php

namespace App\Enums\AcademicCalendars;

enum CourseWorkExtensionStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('academic_calendar.course_work_extension_status_'.$this->value);
    }
}
