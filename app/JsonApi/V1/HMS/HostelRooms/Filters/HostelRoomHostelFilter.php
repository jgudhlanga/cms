<?php

namespace App\JsonApi\V1\HMS\HostelRooms\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelRoomHostelFilter implements Filter
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
            return $query->where('hostel_id', (int) $value);
        }

        $hostelName = trim((string) $value);

        if ($hostelName === '') {
            return $query;
        }

        return $query->whereHas('hostel', function (Builder $q) use ($hostelName): void {
            $q->where('name', 'like', "%{$hostelName}%");
        });
    }
}
