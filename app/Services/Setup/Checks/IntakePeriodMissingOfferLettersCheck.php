<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Institution\IntakePeriod;
use App\Support\Setup\SetupGapResult;

class IntakePeriodMissingOfferLettersCheck implements SetupGapCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::INTAKE_PERIOD_MISSING_OFFER_LETTERS;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $intakes = IntakePeriod::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('status', IntakePeriodStatusEnum::Open)
                    ->orWhere(function ($suspended): void {
                        $suspended
                            ->where('status', IntakePeriodStatusEnum::Suspended)
                            ->where('is_continuous', true);
                    });
            })
            ->whereDoesntHave('offerLetterTemplates')
            ->orderBy('name')
            ->get();

        $gaps = [];

        foreach ($intakes as $intake) {
            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: (int) $intake->tenant_id,
                title: __('setup_gaps.intake_period_missing_offer_letters_title', [
                    'intake' => (string) $intake->name,
                ]),
                body: __('setup_gaps.intake_period_missing_offer_letters_body', [
                    'intake' => (string) $intake->name,
                ]),
                url: route('intake-periods.offer-letter-templates.index', $intake),
                meta: ['intakePeriodId' => (int) $intake->id],
                fingerprintKey: "intake:{$intake->id}|offer-letters",
            );
        }

        return $gaps;
    }
}
