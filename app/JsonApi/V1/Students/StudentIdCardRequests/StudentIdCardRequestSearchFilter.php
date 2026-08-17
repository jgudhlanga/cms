<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Students\StudentIdCardRequests;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class StudentIdCardRequestSearchFilter implements Filter
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
        $term = trim((string) $value);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder->whereHas('student', function (Builder $studentQuery) use ($term): void {
                $studentQuery->where('student_number', 'like', '%'.$term.'%')
                    ->orWhereHas('user', function (Builder $userQuery) use ($term): void {
                        $userQuery->where('first_name', 'like', '%'.$term.'%')
                            ->orWhere('last_name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    });
            });
        });
    }
}
