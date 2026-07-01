<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\StudentApplication;
use App\Queries\Maintenance\VerifiedStudentsForFinalEnrolmentQuery;
use App\Services\Enrolments\StudentBankPaymentMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class VerifiedStudentsForFinalEnrolmentService
{
    private const SUMMARY_CACHE_KEY_PREFIX = 'maintenance:verified-students-final-enrolment:payment-summary:';

    public function __construct(
        protected VerifiedStudentsForFinalEnrolmentQuery $query,
        protected StudentBankPaymentMatcher $paymentMatcher,
    ) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();
        $paymentStatus = $this->normalizePaymentStatus($filters['payment_status'] ?? null);

        if (in_array($paymentStatus, ['eligible', 'no_payment'], true)) {
            return $this->paginateByComputedPaymentStatus($filters, $paymentStatus, $startDate, $endDate);
        }

        $builder = $this->query->applyFilters($this->query->withRelations(), $filters);

        $paginator = $builder
            ->orderBy('student_applications.id')
            ->paginate($this->resolvePerPage());

        /** @var Collection<int, StudentApplication> $applications */
        $applications = $paginator->getCollection();
        $this->applyPaymentEligibilityToCollection($applications, $startDate, $endDate);
        $paginator->setCollection($applications);

        return $paginator;
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     */
    public function resolveBasicMeta(array $filters = []): array
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();
        $summary = $this->resolveFilteredSummaryCounts($filters, $startDate, $endDate);
        $cachedPaymentSummary = Cache::get($this->summaryCacheKey($startDate, $endDate, $filters));

        if ($this->normalizePaymentStatus($filters['payment_status'] ?? null) !== null) {
            return [
                'startDate' => $startDate->toDateTimeString(),
                'endDate' => $endDate->toDateTimeString(),
                'summary' => [
                    'total' => $summary['total'],
                    'eligible' => $summary['eligible'],
                    'noPayment' => $summary['noPayment'],
                    'missingStudentNumber' => $summary['missingStudentNumber'],
                    'paymentSummaryReady' => true,
                ],
            ];
        }

        return [
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'summary' => [
                'total' => $summary['total'],
                'eligible' => is_array($cachedPaymentSummary) ? ($cachedPaymentSummary['eligible'] ?? null) : null,
                'noPayment' => is_array($cachedPaymentSummary) ? ($cachedPaymentSummary['noPayment'] ?? null) : null,
                'missingStudentNumber' => $summary['missingStudentNumber'],
                'paymentSummaryReady' => is_array($cachedPaymentSummary),
            ],
        ];
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     */
    public function resolvePaymentSummary(array $filters = []): array
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();

        if ($this->normalizePaymentStatus($filters['payment_status'] ?? null) !== null) {
            $summary = $this->resolveFilteredSummaryCounts($filters, $startDate, $endDate);

            return [
                'startDate' => $startDate->toDateTimeString(),
                'endDate' => $endDate->toDateTimeString(),
                'summary' => [
                    'total' => $summary['total'],
                    'eligible' => $summary['eligible'],
                    'noPayment' => $summary['noPayment'],
                    'missingStudentNumber' => $summary['missingStudentNumber'],
                    'paymentSummaryReady' => true,
                ],
            ];
        }

        $counts = $this->resolveStudentNumberCounts($filters);

        $cachedPaymentSummary = Cache::remember(
            $this->summaryCacheKey($startDate, $endDate, $filters),
            $this->summaryCacheTtlSeconds(),
            function () use ($startDate, $endDate, $filters): array {
                $numbersToCheck = $this->studentNumbersForSummaryQuery($filters)->all();
                $paymentMap = $this->paymentMatcher->matchStudentNumbersInRange($numbersToCheck, $startDate, $endDate);
                $eligible = collect($paymentMap)->filter()->count();

                return [
                    'eligible' => $eligible,
                    'noPayment' => count($numbersToCheck) - $eligible,
                ];
            },
        );

        return [
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'summary' => [
                'total' => $counts['total'],
                'eligible' => $cachedPaymentSummary['eligible'],
                'noPayment' => $cachedPaymentSummary['noPayment'],
                'missingStudentNumber' => $counts['missingStudentNumber'],
                'paymentSummaryReady' => true,
            ],
        ];
    }

    public function forgetSummaryCache(): void
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();

        Cache::forget($this->summaryCacheKey($startDate, $endDate));
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     * @return array{total: int, eligible: int, noPayment: int, missingStudentNumber: int}
     */
    private function resolveFilteredSummaryCounts(array $filters, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $paymentStatus = $this->normalizePaymentStatus($filters['payment_status'] ?? null);
        $baseFilters = $filters;
        unset($baseFilters['payment_status']);

        if ($paymentStatus === 'missing_student_number') {
            $total = $this->studentsJoinQuery($filters)->count();

            return [
                'total' => $total,
                'eligible' => 0,
                'noPayment' => 0,
                'missingStudentNumber' => $total,
            ];
        }

        if (in_array($paymentStatus, ['eligible', 'no_payment'], true)) {
            $eligibleIds = $this->resolveApplicationIdsByPaymentStatus($baseFilters, 'eligible', $startDate, $endDate);
            $noPaymentIds = $this->resolveApplicationIdsByPaymentStatus($baseFilters, 'no_payment', $startDate, $endDate);
            $missingCount = $this->studentsJoinQuery(array_merge($baseFilters, ['payment_status' => 'missing_student_number']))->count();

            return [
                'total' => $paymentStatus === 'eligible' ? count($eligibleIds) : count($noPaymentIds),
                'eligible' => count($eligibleIds),
                'noPayment' => count($noPaymentIds),
                'missingStudentNumber' => $missingCount,
            ];
        }

        $counts = $this->resolveStudentNumberCounts($filters);
        $numbersToCheck = $this->studentNumbersForSummaryQuery($filters)->all();
        $paymentMap = $this->paymentMatcher->matchStudentNumbersInRange($numbersToCheck, $startDate, $endDate);
        $eligible = collect($paymentMap)->filter()->count();

        return [
            'total' => $counts['total'],
            'eligible' => $eligible,
            'noPayment' => count($numbersToCheck) - $eligible,
            'missingStudentNumber' => $counts['missingStudentNumber'],
        ];
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     */
    private function paginateByComputedPaymentStatus(
        array $filters,
        string $paymentStatus,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): LengthAwarePaginator {
        $baseFilters = $filters;
        unset($baseFilters['payment_status']);

        $matchingIds = $this->resolveApplicationIdsByPaymentStatus($baseFilters, $paymentStatus, $startDate, $endDate);
        $perPage = $this->resolvePerPage();
        $page = max(1, (int) request()->input('page', 1));
        $total = count($matchingIds);
        $slice = array_slice($matchingIds, ($page - 1) * $perPage, $perPage);

        if ($slice === []) {
            return new Paginator(
                collect(),
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()],
            );
        }

        /** @var Collection<int, StudentApplication> $applications */
        $applications = $this->query->withRelations()
            ->whereIn('student_applications.id', $slice)
            ->orderBy('student_applications.id')
            ->get();

        $this->applyPaymentEligibilityToCollection($applications, $startDate, $endDate);

        return new Paginator(
            $applications,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     * }  $filters
     * @return list<int>
     */
    private function resolveApplicationIdsByPaymentStatus(
        array $filters,
        string $paymentStatus,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        /** @var Collection<int, StudentApplication> $applications */
        $applications = $this->query->applyFilters($this->query->withRelations(), $filters)
            ->orderBy('student_applications.id')
            ->get();

        $studentNumbers = $applications
            ->map(fn (StudentApplication $application): string => (string) ($application->student?->student_number ?? ''))
            ->filter(fn (string $number): bool => $number !== '')
            ->unique()
            ->values()
            ->all();

        $paymentMap = $this->paymentMatcher->matchStudentNumbersInRange($studentNumbers, $startDate, $endDate);

        $ids = [];

        foreach ($applications as $application) {
            $eligibility = $this->resolvePaymentEligibility($application, $paymentMap);

            if ($eligibility === $paymentStatus) {
                $ids[] = (int) $application->id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, bool>  $paymentMap
     */
    private function resolvePaymentEligibility(StudentApplication $application, array $paymentMap): string
    {
        $studentNumber = (string) ($application->student?->student_number ?? '');

        if ($studentNumber === '') {
            return 'missing_student_number';
        }

        return ($paymentMap[$studentNumber] ?? false) ? 'eligible' : 'no_payment';
    }

    /**
     * @param  Collection<int, StudentApplication>  $applications
     */
    private function applyPaymentEligibilityToCollection(
        Collection $applications,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void {
        $studentNumbers = $applications
            ->map(fn (StudentApplication $application): string => (string) ($application->student?->student_number ?? ''))
            ->filter(fn (string $number): bool => $number !== '')
            ->values()
            ->all();

        $paymentMap = $this->paymentMatcher->hasPaymentForAny($studentNumbers, $startDate, $endDate);

        $applications->each(function (StudentApplication $application) use ($paymentMap): void {
            $studentNumber = (string) ($application->student?->student_number ?? '');

            if ($studentNumber === '') {
                $application->paymentEligibility = 'missing_student_number';
                $application->hasMatchingPayment = false;

                return;
            }

            $hasPayment = $paymentMap[$studentNumber] ?? false;
            $application->hasMatchingPayment = $hasPayment;
            $application->paymentEligibility = $hasPayment ? 'eligible' : 'no_payment';
        });
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     * @return array{total: int, missingStudentNumber: int}
     */
    private function resolveStudentNumberCounts(array $filters = []): array
    {
        $studentNumbers = $this->studentNumbersForSummaryQuery($filters);

        return [
            'total' => $studentNumbers->count(),
            'missingStudentNumber' => $studentNumbers->filter(
                fn (?string $number): bool => $number === null || $number === '',
            )->count(),
        ];
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     * @return Collection<int, string|null>
     */
    private function studentNumbersForSummaryQuery(array $filters = []): Collection
    {
        return $this->studentsJoinQuery($filters)
            ->select('students.student_number')
            ->toBase()
            ->pluck('student_number');
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     * @return Builder<StudentApplication>
     */
    private function studentsJoinQuery(array $filters = []): Builder
    {
        return $this->query->applyFilters(
            $this->query->baseQuery()
                ->join('students', 'students.id', '=', 'student_applications.student_id'),
            $filters,
        );
    }

    private function summaryCacheTtlSeconds(): int
    {
        return (int) config('custom.enrolments.bulk_finalise.summary_cache_ttl_seconds', 300);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     *     payment_status?: string|null,
     * }  $filters
     */
    private function summaryCacheKey(CarbonImmutable $startDate, CarbonImmutable $endDate, array $filters = []): string
    {
        $fingerprint = md5(json_encode([
            'search' => is_string($filters['search'] ?? null) ? $filters['search'] : null,
            'department' => $this->normalizeFilterList($filters['department'] ?? null),
            'level' => $this->normalizeFilterList($filters['level'] ?? null),
            'course' => $this->normalizeFilterList($filters['course'] ?? null),
            'payment_status' => $this->normalizePaymentStatus($filters['payment_status'] ?? null),
        ], JSON_THROW_ON_ERROR));

        return self::SUMMARY_CACHE_KEY_PREFIX.$startDate->toDateString().':'.$endDate->toDateString().':'.$fingerprint;
    }

    private function normalizePaymentStatus(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === 'all') {
            return null;
        }

        return in_array($value, ['eligible', 'no_payment', 'missing_student_number'], true) ? $value : null;
    }

    /**
     * @return list<int|string>
     */
    private function normalizeFilterList(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        return array_values(array_map(static fn (mixed $item): int|string => is_string($item) ? $item : (int) $item, $values));
    }

    private function resolvePerPage(): int
    {
        $pageSize = request()->input('page_size', config('custom.system.pagination_items_per_page', 15));

        if ($pageSize === 'all') {
            return (int) config('custom.system.pagination_max_limit', 1000);
        }

        return max(1, min((int) $pageSize, (int) config('custom.system.pagination_max_limit', 1000)));
    }
}
