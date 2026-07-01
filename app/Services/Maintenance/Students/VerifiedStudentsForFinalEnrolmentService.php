<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\StudentApplication;
use App\Queries\Maintenance\VerifiedStudentsForFinalEnrolmentQuery;
use App\Services\Enrolments\StudentBankPaymentMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
     * }  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();

        $builder = $this->query->applyFilters($this->query->withRelations(), $filters);

        $paginator = $builder
            ->orderBy('student_applications.id')
            ->paginate($this->resolvePerPage());

        /** @var Collection<int, StudentApplication> $applications */
        $applications = $paginator->getCollection();

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

        $paginator->setCollection($applications);

        return $paginator;
    }

    /**
     * Fast counts for the paginated data response (no bank-statement scans).
     *
     * @return array{
     *     startDate: string,
     *     endDate: string,
     *     summary: array{total: int, eligible: int|null, noPayment: int|null, missingStudentNumber: int, paymentSummaryReady: bool}
     * }
     */
    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     * }  $filters
     */
    public function resolveBasicMeta(array $filters = []): array
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();
        $counts = $this->resolveStudentNumberCounts($filters);
        $cachedPaymentSummary = Cache::get($this->summaryCacheKey($startDate, $endDate, $filters));

        return [
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'summary' => [
                'total' => $counts['total'],
                'eligible' => is_array($cachedPaymentSummary) ? ($cachedPaymentSummary['eligible'] ?? null) : null,
                'noPayment' => is_array($cachedPaymentSummary) ? ($cachedPaymentSummary['noPayment'] ?? null) : null,
                'missingStudentNumber' => $counts['missingStudentNumber'],
                'paymentSummaryReady' => is_array($cachedPaymentSummary),
            ],
        ];
    }

    /**
     * @return array{
     *     startDate: string,
     *     endDate: string,
     *     summary: array{total: int, eligible: int, noPayment: int, missingStudentNumber: int, paymentSummaryReady: bool}
     * }
     */
    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
     * }  $filters
     */
    public function resolvePaymentSummary(array $filters = []): array
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->paymentMatcher->resolveDefaultDateRange();
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
     * @return array{total: int, missingStudentNumber: int}
     */
    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
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
     * @return Collection<int, string|null>
     */
    /**
     * @param  array{
     *     search?: string|null,
     *     department?: array<int|string>|int|string|null,
     *     level?: array<int|string>|int|string|null,
     *     course?: array<int|string>|int|string|null,
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
     * }  $filters
     */
    private function summaryCacheKey(CarbonImmutable $startDate, CarbonImmutable $endDate, array $filters = []): string
    {
        $fingerprint = md5(json_encode([
            'search' => is_string($filters['search'] ?? null) ? $filters['search'] : null,
            'department' => $this->normalizeFilterList($filters['department'] ?? null),
            'level' => $this->normalizeFilterList($filters['level'] ?? null),
            'course' => $this->normalizeFilterList($filters['course'] ?? null),
        ], JSON_THROW_ON_ERROR));

        return self::SUMMARY_CACHE_KEY_PREFIX.$startDate->toDateString().':'.$endDate->toDateString().':'.$fingerprint;
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
