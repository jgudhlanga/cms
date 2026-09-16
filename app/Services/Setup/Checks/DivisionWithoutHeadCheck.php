<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Divisions with nobody named as head.
 *
 * UserAccessScope resolves a head of division from the divisions table, not from the role: until
 * `head_of_division_id` is set, anyone holding the Head of Division role quietly falls back to
 * department scope and sees only the departments their staff record is attached to. Nothing errors —
 * they simply never see the rest of their division, here or anywhere else in the system.
 */
class DivisionWithoutHeadCheck implements SetupGapCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::DIVISION_WITHOUT_HEAD;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $divisions = DB::table('divisions')
            ->whereNull('deleted_at')
            ->whereNull('head_of_division_id')
            ->select(['id', 'name'])
            ->get();

        if ($divisions->isEmpty()) {
            return [];
        }

        // Divisions carry no tenant of their own, so they take it from the departments beneath them.
        $tenantByDivision = DB::table('institution_departments')
            ->whereNull('deleted_at')
            ->whereIn('division_id', $divisions->pluck('id'))
            ->select(['division_id', DB::raw('MIN(tenant_id) as tenant_id')])
            ->groupBy('division_id')
            ->pluck('tenant_id', 'division_id');

        $fallbackTenantId = (int) DB::table('tenants')->orderBy('id')->value('id');

        if ($fallbackTenantId < 1) {
            return [];
        }

        $gaps = [];

        foreach ($divisions as $division) {
            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: (int) ($tenantByDivision[$division->id] ?? $fallbackTenantId),
                title: __('setup_gaps.division_without_head_title', ['division' => (string) $division->name]),
                body: __('setup_gaps.division_without_head_body', ['division' => (string) $division->name]),
                url: route('divisions.index'),
                meta: ['divisionId' => (int) $division->id],
                fingerprintKey: "division:{$division->id}",
            );
        }

        return $gaps;
    }
}
