<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Services\Students\IntakePeriodOrderingService;

/**
 * Shared lookups for the checks that read programme configuration against submitted applications.
 *
 * Application-based checks look at the current admissions intake only: historical intakes carry their own
 * long-settled data and re-reporting them every night would bury the gaps that still matter.
 */
abstract class ProgrammeSetupCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function __construct(protected readonly IntakePeriodOrderingService $intakePeriods) {}

    protected function currentIntakePeriodId(): ?int
    {
        return $this->intakePeriods->defaultAdminIntakePeriod()?->id;
    }
}
