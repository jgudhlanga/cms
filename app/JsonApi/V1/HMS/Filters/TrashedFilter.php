<?php

namespace App\JsonApi\V1\HMS\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class TrashedFilter implements Filter
{
    use IsSingular;

    public function __construct(private readonly string $name = 'trashed') {}

    public static function make(string $name = 'trashed'): self
    {
        return new self($name);
    }

    public function key(): string
    {
        return $this->name;
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        if ($value === '1' || $value === 1 || $value === true || $value === 'true') {
            return $query->withTrashed();
        }

        return $query;
    }
}
