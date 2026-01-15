<?php

namespace App\Helpers;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;

class DropdownHelper
{
    public static function getIntakePeriods()
    {
        return cache()->rememberForever('all_intake_periods', fn() => IntakePeriod::where('is_active', 1)->orderByDesc('end_date')->get());
    }
    public static function getModesOfStudy()
    {
        return cache()->rememberForever('all_modes_of_study', fn() => ModeOfStudy::all());
    }

}
