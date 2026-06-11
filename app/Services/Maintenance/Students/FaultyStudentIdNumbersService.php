<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Queries\Maintenance\FaultyStudentIdNumbersQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaultyStudentIdNumbersService
{
    public function __construct(
        protected FaultyStudentIdNumbersQuery $query,
    ) {}

    /**
     * @param  array{search?: string|null}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $builder = $this->query->baseQuery();

        $search = $filters['search'] ?? null;
        if (is_string($search)) {
            $builder = $this->query->applySearch($builder, $search);
        }

        return $builder->paginate()->withQueryString();
    }
}
