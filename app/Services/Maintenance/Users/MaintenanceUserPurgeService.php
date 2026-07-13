<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Users;

use App\Models\Users\User;
use App\Queries\Maintenance\NonEnrolledStudentUsersQuery;
use App\Services\AccountPurge\AccountPurgeArchiveService;
use Illuminate\Validation\ValidationException;

class MaintenanceUserPurgeService
{
    public function __construct(
        private readonly NonEnrolledStudentUsersQuery $query,
        private readonly AccountPurgeArchiveService $archiveService,
    ) {}

    public function isPurgeEligible(User $user, int $tenantId): bool
    {
        if ($user->studentProfile()->exists()) {
            return false;
        }

        return $this->query
            ->baseQuery($tenantId)
            ->withTrashed()
            ->whereKey($user->id)
            ->exists();
    }

    public function purge(User $user, User $purgedBy, string $reason, int $tenantId): void
    {
        if (! $this->isPurgeEligible($user, $tenantId)) {
            throw ValidationException::withMessages([
                'user' => [__('trans.maintenance_users_purge_not_eligible')],
            ]);
        }

        $this->archiveService->purgeUserAccount($user, $purgedBy, $reason, $tenantId);
    }

    /**
     * @param  list<int>  $userIds
     * @return array{purged: list<int>, skipped: list<int>}
     */
    public function purgeMany(array $userIds, User $purgedBy, string $reason, int $tenantId): array
    {
        $purged = [];
        $skipped = [];

        $users = User::query()
            ->withTrashed()
            ->whereIn('id', $userIds)
            ->where('tenant_id', $tenantId)
            ->get();

        foreach ($userIds as $userId) {
            $user = $users->firstWhere('id', $userId);

            if ($user === null || ! $this->isPurgeEligible($user, $tenantId)) {
                $skipped[] = $userId;

                continue;
            }

            $this->purge($user, $purgedBy, $reason, $tenantId);
            $purged[] = $userId;
        }

        return [
            'purged' => $purged,
            'skipped' => $skipped,
        ];
    }
}
