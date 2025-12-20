<?php

namespace App\Helpers;

use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\ModeOfStudyEnum;

class DepartmentHelper
{
    public static function requiredAutoCardFee(string $department): ?string
    {
        $enum = DepartmentEnum::tryFromLabel($department);

        return $enum?->requiresAutoCardFee()
            ? config('custom.system.autoCardFee')
            : null;
    }

    public static function partTimeLevy(string $modeOfStudy): ?string
    {
        $enum = ModeOfStudyEnum::tryFromLabel($modeOfStudy);

        return $enum?->requiresPartTimeLevy()
            ? config('custom.system.partTimeLevy')
            : null;
    }
}
