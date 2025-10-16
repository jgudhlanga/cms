<?php

namespace App\Enums\Shared;

enum DisabilityStatusEnum: string
{
    case YES = 'yes';
    case NO = 'no';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';
}
