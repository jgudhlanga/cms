<?php

namespace App\Enums\Shared;

enum ClassListTypeEnum: string
{
    case PROVISIONAL = 'provisional';
    case VERIFIED = 'verified';
    case WAITING = 'waiting';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
}
