<?php

namespace App\Observers\Students;

use App\Helpers\Helper;
use App\Models\Students\StudentProgram;

class StudentProgramObserver
{
    public function creating(StudentProgram $model): void
    {
        $model->application_tracking_number = Helper::generateModelUniqueNumber(
            $model,
            config('custom.system.application-tracking-number-prefix'),
            config('custom.system.application-tracking-number-suffix'),
        );
    }
}
