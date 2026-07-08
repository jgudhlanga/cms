<?php

namespace App\JsonApi\V1\HMS\HostelAmenities\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelAmenitySearchFilter implements Filter
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

        return $query->where(function (Builder $builder) use ($search): void {
            $builder->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }
}
