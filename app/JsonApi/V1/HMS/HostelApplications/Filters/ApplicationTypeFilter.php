<?php

namespace App\JsonApi\V1\HMS\HostelApplications\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class ApplicationTypeFilter implements Filter
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
        $type = trim((string) $value);

        if ($type === '') {
            return $query;
        }

        return $query->where('type', $type);
    }
}
