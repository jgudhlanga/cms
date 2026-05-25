<?php

namespace App\JsonApi\V1\HMS\HostelRooms\Filters;

use App\Models\HMS\HostelApplication;
use App\Services\HMS\HostelRoomAvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelRoomAvailableForApplicationFilter implements Filter
{
    use IsSingular;

    public function __construct(
        protected HostelRoomAvailabilityService $roomAvailabilityService,
    ) {}

    public function key(): string
    {
        return 'availableForApplication';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $application = HostelApplication::query()
            ->with(['student.gender', 'gender'])
            ->find((int) $value);

        if ($application === null) {
            return $query->whereRaw('0 = 1');
        }

        $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);
        $allowedHostelIds = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($allowedHostelIds === []) {
            return $query->whereRaw('0 = 1');
        }

        $availableRoomIds = collect($allowedHostelIds)
            ->flatMap(fn (int $hostelId) => $this->roomAvailabilityService
                ->availableRoomsForHostel($hostelId)
                ->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if ($availableRoomIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->whereIn('hostel_id', $allowedHostelIds)
            ->whereIn('id', $availableRoomIds);
    }
}
