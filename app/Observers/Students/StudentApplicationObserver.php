<?php

namespace App\Observers\Students;

use App\Helpers\Helper;
use App\Models\Students\StudentApplication;

class StudentApplicationObserver
{
    public function creating(StudentApplication $model): void
    {
        $now = now();

        $model->application_tracking_number = Helper::generateModelUniqueNumber(
            $model,
            config('custom.system.application-tracking-number-prefix').$now->format('y'),
            $now->format('hi').config('custom.system.application-tracking-number-suffix'),
        );
    }
}
