<?php

namespace App\JsonApi\V1\HMS\Hostels\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelTypeFilter implements Filter
{
    use IsSingular;

    private const ALLOWED = ['male', 'female', 'mixed'];

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
        $type = strtolower(trim((string) $value));

        if ($type === '' || $type === 'all' || ! in_array($type, self::ALLOWED, true)) {
            return $query;
        }

        return $query->where($query->getModel()->qualifyColumn('type'), $type);
    }
}
