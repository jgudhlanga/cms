<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\StudentApplication;
use App\Queries\Maintenance\FaultyApplicationsQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaultyApplicationsService
{
    public function __construct(
        protected FaultyApplicationsQuery $query,
    ) {}

    public function paginate(?string $search = null, ?int $perPage = null): LengthAwarePaginator
    {
        $query = $this->query->applySearch($this->query->baseQuery(), $search);

        return $query
            ->paginate($perPage ?? (new StudentApplication)->getPerPage())
            ->withQueryString();
    }
}
