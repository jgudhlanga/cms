<?php

namespace App\JsonApi\V1\HostelRoomAllocations\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AllocationSearchFilter implements Filter
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

        return $query->whereHas('student', function (Builder $q) use ($search): void {
            $q->where(function (Builder $studentQuery) use ($search): void {
                $studentQuery
                    ->where('student_number', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        });
    }
}
