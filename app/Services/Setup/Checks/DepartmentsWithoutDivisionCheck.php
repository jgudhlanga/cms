<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Departments that belong to no division, while divisions are in use.
 *
 * An institution that does not use divisions at all is not misconfigured, so this stays silent unless
 * at least one division exists. Once they are in use, an unlinked department is invisible to division
 * scoping and division reporting.
 *
 * Reported as one gap per tenant rather than one per department: when the structure has not been set up
 * yet every department is unlinked, and thirty separate alerts would bury everything else.
 */
class DepartmentsWithoutDivisionCheck implements SetupGapCheck
{
    /**
     * How many department names to name in the body before summarising.
     */
    private const int NAMES_IN_BODY = 6;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $divisionsExist = DB::table('divisions')->whereNull('deleted_at')->exists();

        if (! $divisionsExist) {
            return [];
        }

        $rows = DB::table('institution_departments as id_')
            ->join('departments as d', 'd.id', '=', 'id_.department_id')
            ->whereNull('id_.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereNull('id_.division_id')
            ->select(['id_.id', 'id_.tenant_id', 'd.name'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $gaps = [];

        foreach ($rows->groupBy('tenant_id') as $tenantId => $departments) {
            $names = $departments->pluck('name')->map(fn ($name): string => (string) $name)->sort()->values();
            $shown = $names->take(self::NAMES_IN_BODY)->implode(', ');
            $remaining = $names->count() - self::NAMES_IN_BODY;

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: (int) $tenantId,
                title: __('setup_gaps.departments_without_division_title', ['count' => $names->count()]),
                body: __('setup_gaps.departments_without_division_body', [
                    'departments' => $remaining > 0 ? $shown.' (+'.$remaining.' more)' : $shown,
                ]),
                url: route('institution-departments.index'),
                meta: [
                    'departmentCount' => $names->count(),
                    'institutionDepartmentIds' => $departments->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                ],
                // Tenant-wide: the gap closes when every department has been linked.
                fingerprintKey: 'departments-without-division',
            );
        }

        return $gaps;
    }
}
