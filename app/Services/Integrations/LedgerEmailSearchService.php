<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Http\Resources\Integrations\LedgerResource;
use App\Models\HMS\HostelApplication;
use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @phpstan-type PersonPayload array{
 *     name: string,
 *     email: string|null,
 *     phone: string|null,
 *     studentNumber: string|null,
 *     department: string|null,
 *     course: string|null,
 *     level: string|null,
 *     modeOfStudy: string|null
 * }
 * @phpstan-type LedgerGroup array{type: string, label: string, ledgers: array<int, mixed>}
 */
class LedgerEmailSearchService
{
    /**
     * Ledgerable models that hang off a student record.
     *
     * @var list<class-string>
     */
    private const array STUDENT_LEDGERABLES = [
        HostelApplication::class,
        StudentApplication::class,
        StudentIdCardRequest::class,
    ];

    private const string UNKNOWN_FEE_TYPE = 'other';

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
        return User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($search)])
            ->with('studentProfile')
            ->first();
    }

    public function findUserByStudentNumber(string $search): ?User
    {
        $student = Student::query()
            ->whereRaw('LOWER(student_number) = ?', [strtolower($search)])
            ->with('user.studentProfile')
            ->first();

        return $student?->user;
    }

    /**
     * @return array{
     *     matchedBy: 'reference'|'email'|'student_number',
     *     person: PersonPayload|null,
     *     groups: list<LedgerGroup>
     * }
     */
    public function search(string $query, ?string $feeTypeSlug = null): array
    {
        $term = trim($query);

        if (str_contains($term, '@')) {
            return $this->searchByPerson($this->findUserByEmail($term), 'email', $feeTypeSlug);
        }

        $reference = $this->findByReference($term, withTrashed: true);

        if ($reference !== null) {
            return $this->formatReferenceResult($reference, $feeTypeSlug);
        }

        return $this->searchByPerson($this->findUserByStudentNumber($term), 'student_number', $feeTypeSlug);
    }

    public function resolveLedgerForStatusCheck(string $orderReference, ?string $feeTypeSlug = null): ?Ledger
    {
        $term = trim($orderReference);
        $reference = $this->findByReference($term);

        if ($reference !== null) {
            return $reference;
        }

        $user = str_contains($term, '@')
            ? $this->findUserByEmail($term)
            : $this->findUserByStudentNumber($term);

        if ($user === null) {
            return null;
        }

        return $this->ledgerQueryForUser($user)
            ->when($feeTypeSlug !== null, fn (Builder $query) => $query->whereHas(
                'feeType',
                fn (Builder $feeType) => $feeType->where('slug', $feeTypeSlug),
            ))
            ->latest('id')
            ->first();
    }

    /**
     * Every invoice raised for a person, whichever record the payment was attached to.
     *
     * @return EloquentCollection<int, Ledger>
     */
    public function invoicesForUser(User $user): EloquentCollection
    {
        return $this->ledgerQueryForUser($user)
            ->where('type', 'invoice')
            ->with(['feeType', 'level'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Ledger>
     */
    public function invoicesForReferenceLedger(Ledger $reference): EloquentCollection
    {
        return Ledger::query()
            ->withTrashed()
            ->where('ledgerable_id', $reference->ledgerable_id)
            ->where('ledgerable_type', $reference->ledgerable_type)
            ->where('type', 'invoice')
            ->with(['feeType', 'level'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{matchedBy: 'email'|'student_number', person: PersonPayload, groups: list<LedgerGroup>}
     */
    private function searchByPerson(?User $user, string $matchedBy, ?string $feeTypeSlug): array
    {
        if ($user === null) {
            throw new InvalidArgumentException('not_found');
        }

        $groups = $this->groupByFeeType($this->invoicesForUser($user), $feeTypeSlug);

        if ($groups === []) {
            throw new InvalidArgumentException('not_found');
        }

        return [
            'matchedBy' => $matchedBy,
            'person' => $this->personPayload($user),
            'groups' => $groups,
        ];
    }

    /**
     * @return array{matchedBy: 'reference', person: PersonPayload|null, groups: list<LedgerGroup>}
     */
    private function formatReferenceResult(Ledger $reference, ?string $feeTypeSlug): array
    {
        $groups = $this->groupByFeeType($this->invoicesForReferenceLedger($reference), $feeTypeSlug);

        if ($groups === []) {
            throw new InvalidArgumentException('not_found');
        }

        $user = $this->resolveUserForLedger($reference);

        return [
            'matchedBy' => 'reference',
            'person' => $user !== null ? $this->personPayload($user) : null,
            'groups' => $groups,
        ];
    }

    /**
     * @param  EloquentCollection<int, Ledger>  $invoices
     * @return list<LedgerGroup>
     */
    private function groupByFeeType(EloquentCollection $invoices, ?string $feeTypeSlug): array
    {
        return $invoices
            ->when(
                $feeTypeSlug !== null,
                fn (EloquentCollection $ledgers) => $ledgers->filter(
                    fn (Ledger $ledger): bool => $ledger->feeType?->slug === $feeTypeSlug,
                ),
            )
            ->groupBy(fn (Ledger $ledger): string => $ledger->feeType?->slug ?? self::UNKNOWN_FEE_TYPE)
            ->sortBy(fn (Collection $group): int => (int) ($group->first()->feeType?->position ?? PHP_INT_MAX))
            ->map(fn (Collection $group, string $slug): array => [
                'type' => $slug,
                'label' => $group->first()->feeType?->name ?? __('integrations.payments_debug_other_fee'),
                'ledgers' => LedgerResource::collection($group)->resolve(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return PersonPayload
     */
    private function personPayload(User $user): array
    {
        $user->loadMissing('studentProfile');

        $programme = $this->programmeFor($user->studentProfile);

        return [
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'studentNumber' => $user->studentProfile?->student_number,
            'department' => $programme?->institutionDepartment?->department?->name,
            'course' => $programme?->departmentCourse?->course?->name,
            'level' => $programme?->departmentLevel?->level?->name,
            'modeOfStudy' => $programme?->modeOfStudy?->name,
        ];
    }

    /**
     * The enrolment a student is sitting on, or their latest application when they have not enrolled yet.
     */
    private function programmeFor(?Student $student): StudentEnrolment|StudentApplication|null
    {
        if ($student === null) {
            return null;
        }

        $student->loadMissing($this->programmeRelations('latestEnrolment'));

        if ($student->latestEnrolment !== null) {
            return $student->latestEnrolment;
        }

        $student->loadMissing($this->programmeRelations('latestApplication'));

        return $student->latestApplication;
    }

    /**
     * @return list<string>
     */
    private function programmeRelations(string $relation): array
    {
        return [
            $relation.'.institutionDepartment.department',
            $relation.'.departmentCourse.course',
            $relation.'.departmentLevel.level',
            $relation.'.modeOfStudy',
        ];
    }

    /**
     * Ledgers of every fee type that belong to a person, across all ledgerable records.
     *
     * @return Builder<Ledger>
     */
    private function ledgerQueryForUser(User $user): Builder
    {
        $studentId = $user->studentProfile?->id;

        return Ledger::query()->where(function (Builder $builder) use ($user, $studentId): void {
            $builder
                ->where(fn (Builder $query) => $query
                    ->where('ledgerable_type', User::class)
                    ->where('ledgerable_id', $user->id))
                ->orWhere(fn (Builder $query) => $query
                    ->where('ledgerable_type', ApplicationFee::class)
                    ->whereIn(
                        'ledgerable_id',
                        ApplicationFee::query()->where('user_id', $user->id)->select('id'),
                    ));

            if ($studentId === null) {
                return;
            }

            foreach (self::STUDENT_LEDGERABLES as $ledgerable) {
                $builder->orWhere(fn (Builder $query) => $query
                    ->where('ledgerable_type', $ledgerable)
                    ->whereIn(
                        'ledgerable_id',
                        $ledgerable::query()->where('student_id', $studentId)->select('id'),
                    ));
            }
        });
    }

    private function resolveUserForLedger(Ledger $ledger): ?User
    {
        if ($ledger->ledgerable_type === User::class) {
            return User::query()->find($ledger->ledgerable_id);
        }

        $ledgerable = $ledger->ledgerable;

        if ($ledgerable instanceof ApplicationFee) {
            return $ledgerable->user;
        }

        if (in_array($ledger->ledgerable_type, self::STUDENT_LEDGERABLES, true)) {
            return $ledgerable?->student?->user;
        }

        return null;
    }
}
