<?php

namespace App\Console\Commands\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Models\HMS\HostelApplication;
use Illuminate\Console\Command;

class ExpireUnpaidHostelApplicationsCommand extends Command
{
    protected $signature = 'hms:expire-unpaid-applications';

    protected $description = 'Decline hostel applications that have passed their accommodation payment deadline';

    public function handle(): int
    {
        $expired = HostelApplication::query()
            ->whereIn('status', [
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
                HostelApplicationStatusEnum::PARTIALLY_PAID,
            ])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $application) {
            $application->update([
                'status' => HostelApplicationStatusEnum::DECLINED,
                'decline_reason' => __('hms.payment_deadline_expired'),
            ]);
            $count++;
        }

        $this->info("Declined {$count} expired hostel application(s).");

        return self::SUCCESS;
    }
}
