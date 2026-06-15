<?php

namespace App\JsonApi\V1\HMS\HostelRooms\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelRoomSearchFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'search';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $search = trim((string) $value);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search): void {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('floor_number', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('room_type', 'like', "%{$search}%");
        });
    }
}
