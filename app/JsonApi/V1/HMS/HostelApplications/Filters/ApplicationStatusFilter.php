<?php

namespace App\JsonApi\V1\HMS\HostelApplications\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class ApplicationStatusFilter implements Filter
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
        $status = trim((string) $value);

        if ($status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }
}
