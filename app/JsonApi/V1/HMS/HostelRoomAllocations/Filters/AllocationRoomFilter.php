<?php

namespace App\JsonApi\V1\HMS\HostelRoomAllocations\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationRoomFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'room';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $roomName = trim((string) $value);

        if ($roomName === '') {
            return $query;
        }

        return $query->whereHas('room', fn (Builder $q) => $q->where('name', 'like', "%{$roomName}%"));
    }
}
