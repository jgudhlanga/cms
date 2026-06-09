<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Queries\Maintenance\NonEnrolledStudentUsersQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NonEnrolledStudentUsersService
{
    public function __construct(
        protected NonEnrolledStudentUsersQuery $query,
    ) {}

    /**
     * @param  array{search?: string|null}  $filters
     */
    public function paginate(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $builder = $this->query->baseQuery($tenantId);

        $search = $filters['search'] ?? null;
        if (is_string($search)) {
            $builder = $this->query->applySearch($builder, $search);
        }

        return $builder->paginate()->withQueryString();
    }
}
