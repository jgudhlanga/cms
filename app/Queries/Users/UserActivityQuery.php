<?php

declare(strict_types=1);

namespace App\Queries\Users;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class UserActivityQuery
{
    /**
     * @param  Builder<Activity>  $query
     * @param  array{
     *     search?: string|null,
     *     event?: string|null,
     *     log_name?: string|null,
     *     from?: string|null,
     *     to?: string|null,
     *     per_page?: int,
     * }  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->apply($query, $filters)
            ->with(['subject', 'causer'])
            ->latest()
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return list<string>
     */
    public function logNames(Builder $query): array
    {
        return $query
            ->clone()
            ->whereNotNull('log_name')
            ->where('log_name', '!=', '')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Activity>  $query
     * @param  array{
     *     search?: string|null,
     *     event?: string|null,
     *     log_name?: string|null,
     *     from?: string|null,
     *     to?: string|null,
     * }  $filters
     * @return Builder<Activity>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $event = (string) ($filters['event'] ?? '');
        $logName = trim((string) ($filters['log_name'] ?? ''));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        return $query
            ->when(
                $search !== '',
                function (Builder $builder) use ($search): void {
                    $like = '%'.addcslashes($search, '%_\\').'%';

                    $builder->where(function (Builder $searchQuery) use ($like): void {
                        $searchQuery
                            ->where('log_name', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('properties', 'like', $like);
                    });
                },
            )
            ->when(
                in_array($event, ['created', 'updated', 'deleted'], true),
                function (Builder $builder) use ($event): void {
                    $builder->where(function (Builder $eventQuery) use ($event): void {
                        $eventQuery
                            ->where('event', $event)
                            ->orWhere('description', $event);
                    });
                },
            )
            ->when(
                $logName !== '',
                fn (Builder $builder): Builder => $builder->where('log_name', $logName),
            )
            ->when(
                is_string($from) && $from !== '',
                fn (Builder $builder): Builder => $builder->where(
                    'created_at',
                    '>=',
                    CarbonImmutable::parse($from)->startOfDay(),
                ),
            )
            ->when(
                is_string($to) && $to !== '',
                fn (Builder $builder): Builder => $builder->where(
                    'created_at',
                    '<=',
                    CarbonImmutable::parse($to)->endOfDay(),
                ),
            );
    }
}
