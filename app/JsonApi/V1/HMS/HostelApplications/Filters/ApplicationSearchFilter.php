<?php

namespace App\JsonApi\V1\HMS\HostelApplications\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class ApplicationSearchFilter implements Filter
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

        return $query->where(function (Builder $applicationQuery) use ($search): void {
            $applicationQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('student', function (Builder $studentQuery) use ($search): void {
                    $studentQuery->where(function (Builder $q) use ($search): void {
                        $q->where('student_number', 'like', "%{$search}%")
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
        });
    }
}
