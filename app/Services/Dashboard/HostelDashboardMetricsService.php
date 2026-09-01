<?php

namespace App\Services\Dashboard;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Enums\Shared\DisabilityStatusEnum;
use App\Helpers\Helper;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelQuery;
use App\Models\HMS\HostelRoomAllocation;
use App\Repositories\HMS\interface\IHostelRoomRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HostelDashboardMetricsService
{
    public function __construct(protected IHostelRoomRepository $hostelRoomRepository) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     blocks: list<array<string, mixed>>,
     *     genderSplit: array{male: int, female: int, other: int},
     *     queryStats: array<string, int>,
     *     applicationStats: array<string, int|float>,
     *     amenityStatus: array{working: int, needsAttention: int, total: int}
     * }
     */
    public function build(): array
    {
        $hostels = $this->hostelQuery()->get();
        $roomStats = $this->hostelRoomRepository->statsForIndex();

        $totalCapacity = (int) $hostels->sum('capacity');
        $occupiedBeds = (int) $hostels->sum('occupied_beds_sum');
        $availableBeds = max(0, $totalCapacity - $occupiedBeds);
        $occupancyRate = $totalCapacity > 0 ? (int) round(($occupiedBeds / $totalCapacity) * 100) : 0;

        return [
            'summary' => [
                'blocks' => $hostels->count(),
                'totalCapacity' => $totalCapacity,
                'totalRooms' => (int) $roomStats['total_rooms'],
                'occupiedBeds' => $occupiedBeds,
                'availableBeds' => $availableBeds,
                'occupancyRate' => $occupancyRate,
                'vacantRooms' => (int) $roomStats['vacant_count'],
                'totalMaxOccupancy' => (int) $roomStats['total_max_occupancy'],
                'disabledStudents' => $this->disabledStudentsCount(),
            ],
            'blocks' => $this->blocks($hostels),
            'genderSplit' => $this->genderSplit(),
            'queryStats' => $this->queryStats(),
            'applicationStats' => $this->applicationStats(),
            'amenityStatus' => $this->amenityStatusSummary(),
        ];
    }

    private function hostelQuery()
    {
        $query = Hostel::query()
            ->withSum('rooms as occupied_beds_sum', 'current_occupancy')
            ->withCount([
                'rooms as vacant_rooms_count' => fn ($builder) => $builder->where('status', 'vacant'),
                'rooms as maintenance_rooms_count' => fn ($builder) => $builder->where('status', 'maintenance'),
            ])
            ->orderBy('name');

        $hostelIds = Helper::resolveUserHostels();
        if ($hostelIds !== null) {
            $query->whereIn('id', $hostelIds);
        }

        return $query;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(Collection $hostels): array
    {
        return $hostels->map(function (Hostel $hostel): array {
            $capacity = (int) $hostel->capacity;
            $occupied = (int) ($hostel->occupied_beds_sum ?? 0);
            $available = max(0, $capacity - $occupied);
            $occupancyRate = $capacity > 0 ? (int) round(($occupied / $capacity) * 100) : 0;
            $maintenanceRooms = (int) ($hostel->maintenance_rooms_count ?? 0);
            $vacantRooms = (int) ($hostel->vacant_rooms_count ?? 0);

            return [
                'id' => (int) $hostel->id,
                'name' => $hostel->name,
                'type' => $hostel->type,
                'location' => $hostel->location,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'available' => $available,
                'occupancyRate' => $occupancyRate,
                'maintenanceRooms' => $maintenanceRooms,
                'vacantRooms' => $vacantRooms,
                'subtitle' => $this->blockSubtitle($maintenanceRooms, $vacantRooms),
            ];
        })->values()->all();
    }

    private function blockSubtitle(int $maintenanceRooms, int $vacantRooms): string
    {
        if ($maintenanceRooms > 0) {
            return __('dashboard.hostel_maintenance_rooms', ['count' => $maintenanceRooms]);
        }

        if ($vacantRooms > 0) {
            return __('dashboard.hostel_vacant_rooms', ['count' => $vacantRooms]);
        }

        return __('dashboard.hostel_no_issues');
    }

    /**
     * @return array{male: int, female: int, other: int}
     */
    private function genderSplit(): array
    {
        $query = HostelRoomAllocation::query()
            ->active()
            ->with('student.gender');

        $hostelIds = Helper::resolveUserHostels();
        if ($hostelIds !== null) {
            $query->whereHas('room', fn ($builder) => $builder->whereIn('hostel_id', $hostelIds));
        }

        $allocations = $query->get();

        $male = 0;
        $female = 0;
        $other = 0;

        foreach ($allocations as $allocation) {
            $title = strtolower((string) ($allocation->student?->gender?->title ?? ''));

            if ($title === 'male') {
                $male++;
            } elseif ($title === 'female') {
                $female++;
            } else {
                $other++;
            }
        }

        return [
            'male' => $male,
            'female' => $female,
            'other' => $other,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function queryStats(): array
    {
        $openStatus = HostelQueryStatusEnum::OPEN->value;
        $inProgressStatus = HostelQueryStatusEnum::IN_PROGRESS->value;
        $resolvedStatus = HostelQueryStatusEnum::RESOLVED->value;
        $highPriority = HostelQueryPriorityEnum::HIGH->value;

        $query = HostelQuery::query();
        $hostelIds = Helper::resolveUserHostels();
        if ($hostelIds !== null) {
            $query->whereIn('hostel_id', $hostelIds);
        }

        $stats = $query
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as open_count', [$openStatus])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as in_progress_count', [$inProgressStatus])
            ->selectRaw(
                'sum(case when priority = ? and status in (?, ?) then 1 else 0 end) as high_priority_count',
                [$highPriority, $openStatus, $inProgressStatus],
            )
            ->selectRaw(
                'sum(case when status = ? and updated_at >= ? then 1 else 0 end) as resolved_this_month_count',
                [$resolvedStatus, now()->startOfMonth()->toDateTimeString()],
            )
            ->first();

        return [
            'open' => (int) ($stats->open_count ?? 0),
            'inProgress' => (int) ($stats->in_progress_count ?? 0),
            'highPriority' => (int) ($stats->high_priority_count ?? 0),
            'resolvedThisMonth' => (int) ($stats->resolved_this_month_count ?? 0),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function applicationStats(): array
    {
        $query = HostelApplication::query();
        $hostelIds = Helper::resolveUserHostels();
        if ($hostelIds !== null) {
            $query->whereIn('hostel_id', $hostelIds);
        }

        $counts = $query
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($counts[HostelApplicationStatusEnum::PENDING->value] ?? 0);
        $awaitingPayment = (int) ($counts[HostelApplicationStatusEnum::AWAITING_PAYMENT->value] ?? 0);
        $partiallyPaid = (int) ($counts[HostelApplicationStatusEnum::PARTIALLY_PAID->value] ?? 0);
        $paid = (int) ($counts[HostelApplicationStatusEnum::PAID->value] ?? 0);
        $approved = (int) ($counts[HostelApplicationStatusEnum::APPROVED->value] ?? 0);
        $declined = (int) ($counts[HostelApplicationStatusEnum::DECLINED->value] ?? 0);
        $total = $pending + $awaitingPayment + $partiallyPaid + $paid + $approved + $declined;
        $collected = $paid + $approved;
        $paidRate = $total > 0 ? round(($collected / $total) * 100) : 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'awaitingPayment' => $awaitingPayment,
            'partiallyPaid' => $partiallyPaid,
            'paid' => $paid,
            'approved' => $approved,
            'declined' => $declined,
            'paidRate' => $paidRate,
        ];
    }

    private function disabledStudentsCount(): int
    {
        $query = HostelRoomAllocation::query()
            ->active()
            ->whereHas('student', fn ($builder) => $builder
                ->where('students.disability_status', DisabilityStatusEnum::YES->value));

        $hostelIds = Helper::resolveUserHostels();
        if ($hostelIds !== null) {
            $query->whereHas('room', fn ($builder) => $builder->whereIn('hostel_id', $hostelIds));
        }

        return $query->count();
    }

    /**
     * Room amenities have no pivot status column, so linked amenities count as working
     * and rooms under maintenance count as needing attention.
     *
     * @return array{working: int, needsAttention: int, total: int}
     */
    public function amenityStatusSummary(): array
    {
        $hostels = $this->hostelQuery()->with('rooms.amenities')->get();
        $working = 0;
        $needsAttention = 0;

        foreach ($hostels as $hostel) {
            foreach ($hostel->rooms as $room) {
                $amenityCount = $room->amenities?->count() ?? 0;
                $working += $amenityCount;

                if (strtolower((string) $room->status) === 'maintenance') {
                    $needsAttention++;
                }
            }
        }

        return [
            'working' => $working,
            'needsAttention' => $needsAttention,
            'total' => $working + $needsAttention,
        ];
    }
}
