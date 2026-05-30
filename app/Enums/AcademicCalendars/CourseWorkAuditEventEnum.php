<?php

namespace App\Enums\AcademicCalendars;

enum CourseWorkAuditEventEnum: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
