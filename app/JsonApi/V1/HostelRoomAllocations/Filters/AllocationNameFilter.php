<?php

namespace App\JsonApi\V1\HostelRoomAllocations\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationNameFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'name';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $name = trim((string) $value);

        if ($name === '') {
            return $query;
        }

        return $query->whereHas('student.user', function (Builder $q) use ($name): void {
            $q->where('first_name', 'like', "%{$name}%")
                ->orWhere('middle_name', 'like', "%{$name}%")
                ->orWhere('last_name', 'like', "%{$name}%");
        });
    }
}
