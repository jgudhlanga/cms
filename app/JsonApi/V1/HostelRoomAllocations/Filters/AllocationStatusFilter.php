<?php

namespace App\JsonApi\V1\HostelRoomAllocations\Filters;

use App\Enums\HMS\HostelAllocationStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationStatusFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'status';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $status = HostelAllocationStatusEnum::tryFrom((string) $value);

        if ($status === null) {
            return $query;
        }

        return $query->where('status', $status->value);
    }
}
