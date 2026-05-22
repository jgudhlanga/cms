<?php

namespace App\JsonApi\V1\HostelRoomAllocations\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationHostelFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'hostel';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        if (is_numeric($value)) {
            return $query->whereHas('room', fn (Builder $q) => $q->where('hostel_id', (int) $value));
        }

        $hostelName = trim((string) $value);

        if ($hostelName === '') {
            return $query;
        }

        return $query->whereHas('room.hostel', fn (Builder $q) => $q->where('name', 'like', "%{$hostelName}%"));
    }
}
