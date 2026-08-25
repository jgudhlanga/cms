<?php

namespace App\Console\Commands\Assessments;

use App\Services\Assessments\MissingMarksNotificationService;
use Illuminate\Console\Command;

class SendMissingMarksNotificationsCommand extends Command
{
    protected $signature = 'assessment-calendars:send-missing-marks-notifications';

    protected $description = 'Send missing-marks reminders when assessment calendar notification dates are due';

    public function handle(MissingMarksNotificationService $notificationService): int
    {
        $sent = $notificationService->dispatchDueTiers();

        $this->info("Dispatched {$sent} missing-marks notification tier(s).");

        return self::SUCCESS;
    }
}
