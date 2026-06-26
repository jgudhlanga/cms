<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Users;

use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;
use App\Models\Users\User;
use App\Queries\Maintenance\NonEnrolledStudentUsersQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MaintenanceUserPurgeService
{
    public function __construct(
        private readonly NonEnrolledStudentUsersQuery $query,
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

    public function purge(User $user, int $tenantId): void
    {
        if (! $this->isPurgeEligible($user, $tenantId)) {
            throw ValidationException::withMessages([
                'user' => [__('trans.maintenance_users_purge_not_eligible')],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $this->hardDeleteUserRelations($user);
            $user->forceDelete();
        });
    }

    /**
     * @param  list<int>  $userIds
     * @return array{purged: list<int>, skipped: list<int>}
     */
    public function purgeMany(array $userIds, int $tenantId): array
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

            $this->purge($user, $tenantId);
            $purged[] = $userId;
        }

        return [
            'purged' => $purged,
            'skipped' => $skipped,
        ];
    }

    private function hardDeleteUserRelations(User $user): void
    {
        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $user->notifications()->delete();

        $user->ledgerTransactions()
            ->withTrashed()
            ->get()
            ->each(fn (Ledger $ledger) => $this->forceDeleteLedger($ledger));

        $user->applicationFees()
            ->get()
            ->each(fn (ApplicationFee $applicationFee) => $this->forceDeleteApplicationFee($applicationFee));

        $user->preference()?->delete();

        $this->forceDeleteUserMedia($user);

        $user->syncRoles([]);
        $user->syncPermissions([]);
    }

    private function forceDeleteApplicationFee(ApplicationFee $applicationFee): void
    {
        $applicationFee->ledgerTransactions()
            ->withTrashed()
            ->get()
            ->each(fn (Ledger $ledger) => $this->forceDeleteLedger($ledger));

        $applicationFee->delete();
    }

    private function forceDeleteLedger(Ledger $ledger): void
    {
        if ($ledger->proof_of_payment_id) {
            Media::query()->whereKey($ledger->proof_of_payment_id)->delete();
        }

        $ledger->clearMediaCollection('receipts');
        $ledger->forceDelete();
    }

    private function forceDeleteUserMedia(User $user): void
    {
        $user->clearMediaCollection('user-avatar');

        if ($user->avatar_id) {
            Media::query()->whereKey($user->avatar_id)->delete();
        }

        $user->media()->each(fn (Media $media) => $media->delete());
    }
}
