<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserAccountRelationPurgeService
{
    public function purge(User $user): void
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
