<?php

namespace App\JsonApi\V1\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class HostelWardenFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'warden';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $warden = trim((string) $value);

        if ($warden === '') {
            return $query;
        }

        return $query->whereHas('warden.user', function (Builder $q) use ($warden): void {
            $q->where('first_name', 'like', "%{$warden}%")
                ->orWhere('middle_name', 'like', "%{$warden}%")
                ->orWhere('last_name', 'like', "%{$warden}%");
        });
    }
}
