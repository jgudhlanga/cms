<?php

namespace App\Enums\Assessments;

enum AssessmentWindowEventEnum: string
{
    case Opened = 'opened';
    case Closed = 'closed';
}
