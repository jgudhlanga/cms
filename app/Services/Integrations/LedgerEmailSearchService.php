<?php

namespace App\Services\Integrations;

use App\Enums\Integrations\LedgerEmailSearchTypeEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LedgerEmailSearchService
{
    public function findByReference(string $search, bool $withTrashed = false): ?Ledger
    {
        $query = Ledger::query()
            ->where(function (Builder $builder) use ($search) {
                $builder->where('system_reference', $search)
                    ->orWhere('payment_reference', $search);
            });

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    public function findUserByEmail(string $search): ?User
    {
        return User::query()->where('email', $search)->first();
    }

    /**
     * @return Collection<int, LedgerEmailSearchTypeEnum>
     */
    public function discoverTypes(User $user): Collection
    {
        return collect(LedgerEmailSearchTypeEnum::cases())
            ->filter(fn (LedgerEmailSearchTypeEnum $type) => $this->hasInvoicesForType($user, $type))
            ->values();
    }

    /**
     * @return EloquentCollection<int, Ledger>
     */
    public function resolveInvoices(User $user, LedgerEmailSearchTypeEnum $type): EloquentCollection
    {
        return $this->invoiceQueryForType($user, $type)->get();
    }

    public function resolveReferenceLedger(User $user, LedgerEmailSearchTypeEnum $type): ?Ledger
    {
        return $this->ledgerQueryForType($user, $type)->first();
    }

    public function resolveReferenceLedgerByEmailPriority(
        User $user,
        ?LedgerEmailSearchTypeEnum $type = null,
    ): ?Ledger {
        if ($type !== null) {
            return $this->resolveReferenceLedger($user, $type);
        }

        foreach (LedgerEmailSearchTypeEnum::cases() as $searchType) {
            $ledger = $this->resolveReferenceLedger($user, $searchType);

            if ($ledger !== null) {
                return $ledger;
            }
        }

        return null;
    }

    /**
     * @return EloquentCollection<int, Ledger>
     */
    public function invoicesForReferenceLedger(Ledger $reference): EloquentCollection
    {
        return Ledger::query()
            ->where('ledgerable_id', $reference->ledgerable_id)
            ->where('ledgerable_type', $reference->ledgerable_type)
            ->where('type', 'invoice')
            ->get();
    }

    /**
     * @param  Collection<int, LedgerEmailSearchTypeEnum>  $types
     * @return array<int, array{value: string, label: string}>
     */
    public function formatTypeOptions(Collection $types): array
    {
        return $types
            ->map(fn (LedgerEmailSearchTypeEnum $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    private function hasInvoicesForType(User $user, LedgerEmailSearchTypeEnum $type): bool
    {
        return $this->invoiceQueryForType($user, $type)->exists();
    }

    /**
     * @return Builder<Ledger>
     */
    private function invoiceQueryForType(User $user, LedgerEmailSearchTypeEnum $type): Builder
    {
        return $this->ledgerQueryForType($user, $type)->where('type', 'invoice');
    }

    /**
     * @return Builder<Ledger>
     */
    private function ledgerQueryForType(User $user, LedgerEmailSearchTypeEnum $type): Builder
    {
        return match ($type) {
            LedgerEmailSearchTypeEnum::Legacy => Ledger::query()
                ->where('ledgerable_type', User::class)
                ->where('ledgerable_id', $user->id),
            LedgerEmailSearchTypeEnum::ApplicationFee => Ledger::query()
                ->where('ledgerable_type', ApplicationFee::class)
                ->whereIn(
                    'ledgerable_id',
                    ApplicationFee::query()->where('user_id', $user->id)->select('id'),
                ),
            LedgerEmailSearchTypeEnum::HostelApplication => Ledger::query()
                ->where('ledgerable_type', HostelApplication::class)
                ->whereIn(
                    'ledgerable_id',
                    HostelApplication::query()
                        ->whereHas('student', fn (Builder $query) => $query->where('user_id', $user->id))
                        ->select('id'),
                ),
        };
    }
}
