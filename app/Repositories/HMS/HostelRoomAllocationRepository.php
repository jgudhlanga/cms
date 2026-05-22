<?php

namespace App\Repositories\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Models\HMS\HostelRoomAllocation;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRoomAllocationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class HostelRoomAllocationRepository extends BaseRepository implements IHostelRoomAllocationRepository
{
    public function __construct(protected HostelRoomAllocation $allocation)
    {
        parent::__construct($this->allocation);
    }

    public function paginateForIndex(array $filters = []): LengthAwarePaginator
    {
        $query = $this->allocation->query()
            ->with([
                'student.user',
                'student.gender',
                'student.latestEnrolment.institutionDepartment.department',
                'student.latestEnrolment.departmentLevel.level',
                'student.latestEnrolment.departmentCourse.course',
                'room.hostel',
            ]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->whereHas('student', function ($q) use ($search): void {
                $q->where(function ($studentQuery) use ($search): void {
                    $studentQuery
                        ->where('student_number', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('passport_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            });
        }

        if (! empty($filters['name'])) {
            $name = trim((string) $filters['name']);

            $query->whereHas('student.user', function ($q) use ($name): void {
                $q->where('first_name', 'like', "%{$name}%")
                    ->orWhere('middle_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        $genderIds = $this->intListFromFilter($filters['gender'] ?? null);
        if ($genderIds !== []) {
            $query->whereHas('student', fn ($q) => $q->whereIn('gender_id', $genderIds));
        }

        if (! empty($filters['hostel'])) {
            $hostel = $filters['hostel'];
            if (is_numeric($hostel)) {
                $query->whereHas('room', fn ($q) => $q->where('hostel_id', (int) $hostel));
            } else {
                $hostelName = trim((string) $hostel);
                $query->whereHas('room.hostel', fn ($q) => $q->where('name', 'like', "%{$hostelName}%"));
            }
        }

        if (! empty($filters['room'])) {
            $roomName = trim((string) $filters['room']);
            $query->whereHas('room', fn ($q) => $q->where('name', 'like', "%{$roomName}%"));
        }

        if (! empty($filters['type'])) {
            $type = HostelAllocationTypeEnum::tryFrom((string) $filters['type']);
            if ($type !== null) {
                $query->where('type', $type->value);
            }
        }

        if (! empty($filters['status'])) {
            $status = HostelAllocationStatusEnum::tryFrom((string) $filters['status']);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        } else {
            $query->whereIn('status', HostelAllocationStatusEnum::indexStatuses());
        }

        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        return $query
            ->orderByRaw(
                'CASE hostel_room_allocations.status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END',
                [
                    HostelAllocationStatusEnum::ACTIVE->value,
                    HostelAllocationStatusEnum::CHECKED_OUT->value,
                    HostelAllocationStatusEnum::CLOSED->value,
                ]
            )
            ->latest('hostel_room_allocations.created_at')
            ->paginate($this->allocation->getPerPage())
            ->withQueryString();
    }

    /**
     * @return list<int>
     */
    private function intListFromFilter(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $ids = [];
        foreach ($values as $v) {
            $i = (int) $v;
            if ($i > 0) {
                $ids[] = $i;
            }
        }

        return array_values(array_unique($ids));
    }
}
