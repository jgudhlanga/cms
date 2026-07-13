<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Enums\AccountPurge\AccountPurgeArchiveStatusEnum;
use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\AccountPurge\AccountPurgeArchive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AccountPurgeArchivesListService
{
    /**
     * @param  array{search?: string|null, purge_type?: string|null, status?: string|null}  $filters
     */
    public function paginate(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = AccountPurgeArchive::query()
            ->where('tenant_id', $tenantId)
            ->with(['purgedBy', 'studentNote'])
            ->orderByDesc('purged_at');

        $search = $filters['search'] ?? null;
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->where('summary->name', 'like', $term)
                    ->orWhere('summary->email', 'like', $term)
                    ->orWhere('summary->student_number', 'like', $term);
            });
        }

        $purgeType = AccountPurgeTypeEnum::tryFromFilter($filters['purge_type'] ?? null);
        if ($purgeType !== null) {
            $query->where('purge_type', $purgeType);
        }

        $status = AccountPurgeArchiveStatusEnum::tryFromFilter($filters['status'] ?? null);
        if ($status === AccountPurgeArchiveStatusEnum::ACTIVE) {
            $query->active();
        } elseif ($status === AccountPurgeArchiveStatusEnum::FLUSHED) {
            $query->flushed();
        } elseif ($status === AccountPurgeArchiveStatusEnum::RESTORED) {
            $query->restored();
        }

        return $query->paginate($this->resolvePerPage())->withQueryString();
    }

    private function resolvePerPage(): int
    {
        $pageSize = request()->input('page_size', config('custom.system.pagination_items_per_page', 15));

        return max(1, (int) $pageSize);
    }
}
