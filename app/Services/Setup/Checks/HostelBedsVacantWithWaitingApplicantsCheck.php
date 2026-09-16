<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Beds standing empty while approved applicants have nowhere to sleep.
 *
 * Spare capacity on its own is normal, so this only speaks up when both are true at once: a hostel has
 * vacant beds and the tenant has approved applicants with no active allocation. Waiting applicants are
 * counted per tenant (an application is not tied to a hostel until it is placed), and the gap is raised
 * against each hostel that could take them.
 */
class HostelBedsVacantWithWaitingApplicantsCheck implements SetupGapCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::HOSTEL_BEDS_VACANT_WITH_WAITING_APPLICANTS;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $waitingByTenant = DB::table('hostel_applications as ha')
            ->where('ha.status', HostelApplicationStatusEnum::APPROVED->value)
            ->whereNotNull('ha.student_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('hostel_room_allocations as hra')
                    ->whereColumn('hra.student_id', 'ha.student_id')
                    ->whereNull('hra.deleted_at')
                    ->where('hra.status', HostelAllocationStatusEnum::ACTIVE->value);
            })
            ->groupBy('ha.tenant_id')
            ->select(['ha.tenant_id', DB::raw('COUNT(*) as waiting_count')])
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->tenant_id => (int) $row->waiting_count]);

        if ($waitingByTenant->isEmpty()) {
            return [];
        }

        $hostels = DB::table('hostel_rooms as hr')
            ->join('hostels as h', 'h.id', '=', 'hr.hostel_id')
            ->whereNull('hr.deleted_at')
            ->whereNull('h.deleted_at')
            ->whereIn('h.tenant_id', $waitingByTenant->keys())
            ->whereColumn('hr.current_occupancy', '<', 'hr.max_occupancy')
            ->groupBy('h.id', 'h.tenant_id', 'h.name')
            ->select([
                'h.id',
                'h.tenant_id',
                'h.name',
                DB::raw('SUM(hr.max_occupancy - hr.current_occupancy) as vacant_beds'),
            ])
            ->get();

        $gaps = [];

        foreach ($hostels as $hostel) {
            $tenantId = (int) $hostel->tenant_id;
            $waiting = $waitingByTenant->get($tenantId, 0);
            $vacantBeds = (int) $hostel->vacant_beds;

            if ($waiting < 1 || $vacantBeds < 1) {
                continue;
            }

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $tenantId,
                title: __('setup_gaps.hostel_beds_vacant_with_waiting_applicants_title', [
                    'hostel' => (string) $hostel->name,
                    'beds' => $vacantBeds,
                    'applicants' => $waiting,
                ]),
                body: __('setup_gaps.hostel_beds_vacant_with_waiting_applicants_body'),
                url: route('hostels.show', ['hostel' => $hostel->id]),
                meta: [
                    'hostelId' => (int) $hostel->id,
                    'vacantBeds' => $vacantBeds,
                    'waitingApplicants' => $waiting,
                ],
                fingerprintKey: "hostel:{$hostel->id}",
            );
        }

        return $gaps;
    }
}
