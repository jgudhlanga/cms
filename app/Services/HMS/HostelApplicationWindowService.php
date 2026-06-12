<?php

namespace App\Services\HMS;

use App\Models\HMS\HmsSetting;
use Carbon\CarbonInterface;

class HostelApplicationWindowService
{
    public const BLOCKER_APPLICATIONS_CLOSED = 'applications_closed';

    public const BLOCKER_APPLICATION_DATES_NOT_CONFIGURED = 'application_dates_not_configured';

    public const BLOCKER_APPLICATIONS_NOT_YET_OPEN = 'applications_not_yet_open';

    public const BLOCKER_APPLICATIONS_PERIOD_ENDED = 'applications_period_ended';

    /**
     * @return array{success: bool, blocker: string|null}
     */
    public function windowStatus(?int $tenantId = null, ?CarbonInterface $asOf = null): array
    {
        $settings = HmsSetting::resolveForTenant($tenantId);

        if (! $settings->applications_open) {
            return $this->failure(self::BLOCKER_APPLICATIONS_CLOSED);
        }

        return [
            'success' => true,
            'blocker' => null,
        ];
    }

    /**
     * @return array{checkIn: string|null, checkOut: string|null}
     */
    public function configuredApplicationDates(?int $tenantId = null): array
    {
        $settings = HmsSetting::resolveForTenant($tenantId);

        return [
            'checkIn' => $settings->application_start_date?->toDateString(),
            'checkOut' => $settings->application_end_date?->toDateString(),
        ];
    }

    /**
     * @return array{success: false, blocker: string}
     */
    private function failure(string $blocker): array
    {
        return [
            'success' => false,
            'blocker' => $blocker,
        ];
    }
}
