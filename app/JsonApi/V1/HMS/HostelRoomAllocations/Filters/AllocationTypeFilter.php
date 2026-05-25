<?php

namespace App\JsonApi\V1\HMS\HostelRoomAllocations\Filters;

use App\Enums\HMS\HostelAllocationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationTypeFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'type';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $type = HostelAllocationTypeEnum::tryFrom((string) $value);

        if ($type === null) {
            return $query;
        }

        return $query->where('type', $type->value);
    }
}
