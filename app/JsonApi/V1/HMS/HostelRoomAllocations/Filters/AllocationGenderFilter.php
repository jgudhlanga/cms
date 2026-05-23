<?php

namespace App\JsonApi\V1\HMS\HostelRoomAllocations\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationGenderFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'gender';
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value): Builder
    {
        $ids = $this->intListFromValue($value);

        if ($ids === []) {
            return $query;
        }

        return $query->whereHas('student', fn (Builder $q) => $q->whereIn('gender_id', $ids));
    }

    /**
     * @return list<int>
     */
    private function intListFromValue(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (is_string($value) && str_contains($value, ',')) {
            $values = explode(',', $value);
        } else {
            $values = is_array($value) ? $value : [$value];
        }

        $ids = [];
        foreach ($values as $v) {
            $i = (int) $v;
            if ($i > 0) {
                $ids[] = $i;
            }
        }

        return array_values(array_unique($ids));
    }
}
