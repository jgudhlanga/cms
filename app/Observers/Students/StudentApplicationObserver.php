<?php

namespace App\Observers\Students;

use App\Helpers\Helper;
use App\Models\Students\StudentApplication;

class StudentApplicationObserver
{
    public function creating(StudentApplication $model): void
    {
        $model->application_tracking_number = Helper::generateModelUniqueNumber(
            $model,
            config('custom.system.application-tracking-number-prefix'),
            config('custom.system.application-tracking-number-suffix'),
        );
    }
}
