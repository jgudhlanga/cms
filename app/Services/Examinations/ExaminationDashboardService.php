<?php

declare(strict_types=1);

namespace App\Services\Examinations;

use App\Enums\Students\StudentExamResultComment;
use App\Queries\Examinations\ExaminationResultQuery;

class ExaminationDashboardService
{
    public function __construct(
        private readonly ExaminationResultQuery $query,
    ) {}

    /**
     * @param  array{
     *     session: string|null,
     *     discipline: string|null,
     *     subject_code: string|null,
     *     compare_session: string|null,
     * }  $filters
     * @return array{
     *     statusCounts: array<string, int>,
     *     statusLabels: array<string, string>,
     *     totalCandidates: int,
     *     passRate: float|null,
     *     onlineViewedCount: int,
     *     onlineViewedRate: float|null,
     *     comparison: array{
     *         primaryPassRate: float|null,
     *         comparePassRate: float|null,
     *         modules: list<array{
     *             subjectCode: string,
     *             subject: string|null,
     *             primaryPassRate: float|null,
     *             comparePassRate: float|null,
     *             delta: float|null,
     *             trend: 'improved'|'declined'|'unchanged',
     *         }>
     *     }|null
     * }
     */
    public function build(array $filters): array
    {
        $primaryFilters = [
            'session' => $filters['session'] ?? null,
            'discipline' => $filters['discipline'] ?? null,
            'subject_code' => $filters['subject_code'] ?? null,
        ];

        $statusCounts = $this->statusCounts($primaryFilters);
        $totalCandidates = array_sum($statusCounts);
        $passCount = ($statusCounts[StudentExamResultComment::Award->value] ?? 0)
            + ($statusCounts[StudentExamResultComment::Proceed->value] ?? 0);
        $passRate = $this->rate($passCount, $totalCandidates);
        $onlineViewedCount = $this->query->onlineViewedCount($primaryFilters);
        $onlineViewedRate = $this->rate($onlineViewedCount, $totalCandidates);

        $comparison = null;
        if (filled($filters['compare_session'] ?? null) && filled($filters['session'] ?? null)) {
            $compareFilters = [
                'session' => $filters['compare_session'],
                'discipline' => $filters['discipline'] ?? null,
                'subject_code' => $filters['subject_code'] ?? null,
            ];

            $compareStatus = $this->statusCounts($compareFilters);
            $compareTotal = array_sum($compareStatus);
            $comparePass = ($compareStatus[StudentExamResultComment::Award->value] ?? 0)
                + ($compareStatus[StudentExamResultComment::Proceed->value] ?? 0);

            $comparison = [
                'primaryPassRate' => $passRate,
                'comparePassRate' => $this->rate($comparePass, $compareTotal),
                'modules' => $this->moduleComparisons($primaryFilters, $compareFilters),
            ];
        }

        return [
            'statusCounts' => $statusCounts,
            'statusLabels' => $this->statusLabels(),
            'totalCandidates' => $totalCandidates,
            'passRate' => $passRate,
            'onlineViewedCount' => $onlineViewedCount,
            'onlineViewedRate' => $onlineViewedRate,
            'comparison' => $comparison,
        ];
    }

    /**
     * @param  array{
     *     session?: string|null,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     compare_session?: string|null,
     * }  $requestFilters
     * @return array{
     *     filters: array{
     *         session: string|null,
     *         discipline: string|null,
     *         subject_code: string|null,
     *         compare_session: string|null,
     *     },
     *     filterOptions: array{
     *         sessions: list<array{value: string, label: string}>,
     *         disciplines: list<array{value: string, label: string}>,
     *         subjects: list<array{value: string, label: string}>,
     *         compareSessions: list<array{value: string, label: string}>,
     *     },
     *     statusCounts: array<string, int>,
     *     statusLabels: array<string, string>,
     *     chartLabels: array<string, string>,
     *     totalCandidates: int,
     *     passRate: float|null,
     *     onlineViewedCount: int,
     *     onlineViewedRate: float|null,
     *     comparison: array<string, mixed>|null,
     * }
     */
    public function pagePayload(array $requestFilters): array
    {
        $filters = $this->query->resolveFilters($requestFilters);
        $dashboard = $this->build($filters);

        $sessionOptions = $this->query->sessionOptions();
        $compareSessionOptions = array_values(array_filter(
            $sessionOptions,
            fn (array $option): bool => $option['value'] !== ($filters['session'] ?? null),
        ));

        return [
            'filters' => [
                'session' => $filters['session'],
                'discipline' => $filters['discipline'],
                'subject_code' => $filters['subject_code'],
                'compare_session' => $filters['compare_session'],
            ],
            'filterOptions' => [
                'sessions' => $sessionOptions,
                'disciplines' => $this->query->disciplineOptions($filters['session']),
                'subjects' => $this->query->subjectOptions($filters['session'], $filters['discipline']),
                'compareSessions' => $compareSessionOptions,
            ],
            'statusCounts' => $dashboard['statusCounts'],
            'statusLabels' => $dashboard['statusLabels'],
            'chartLabels' => [
                'session' => __('examinations.session'),
                'compareSession' => __('examinations.compare_session'),
                'passRate' => __('examinations.pass_rate'),
                'modulePassPrimary' => __('examinations.module_pass_primary'),
                'modulePassCompare' => __('examinations.module_pass_compare'),
                'moduleImproved' => __('examinations.module_improved'),
                'moduleDeclined' => __('examinations.module_declined'),
                'moduleUnchanged' => __('examinations.module_unchanged'),
            ],
            'totalCandidates' => $dashboard['totalCandidates'],
            'passRate' => $dashboard['passRate'],
            'onlineViewedCount' => $dashboard['onlineViewedCount'],
            'onlineViewedRate' => $dashboard['onlineViewedRate'],
            'comparison' => $dashboard['comparison'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusLabels(): array
    {
        $labels = [];

        foreach (StudentExamResultComment::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }

    /**
     * @param  array{
     *     session: string|null,
     *     discipline: string|null,
     *     subject_code: string|null,
     * }  $filters
     * @return array<string, int>
     */
    public function statusCounts(array $filters): array
    {
        $counts = array_fill_keys(
            array_map(fn (StudentExamResultComment $case): string => $case->value, StudentExamResultComment::cases()),
            0,
        );

        if (! filled($filters['session'] ?? null)) {
            return $counts;
        }

        foreach ($this->query->statusCountsByComment($filters) as $comment => $count) {
            $normalized = StudentExamResultComment::tryFromCourseComment($comment);
            if ($normalized === null) {
                continue;
            }

            $counts[$normalized->value] += $count;
        }

        return $counts;
    }

    /**
     * @param  array{session: string|null, discipline: string|null, subject_code: string|null}  $primaryFilters
     * @param  array{session: string|null, discipline: string|null, subject_code: string|null}  $compareFilters
     * @return list<array{
     *     subjectCode: string,
     *     subject: string|null,
     *     primaryPassRate: float|null,
     *     comparePassRate: float|null,
     *     delta: float|null,
     *     trend: 'improved'|'declined'|'unchanged',
     * }>
     */
    private function moduleComparisons(array $primaryFilters, array $compareFilters): array
    {
        $primaryModules = $this->query->modulePassRates($primaryFilters)->keyBy('subject_code');
        $compareModules = $this->query->modulePassRates($compareFilters)->keyBy('subject_code');

        $subjectCodes = $primaryModules->keys()->intersect($compareModules->keys())->sort()->values();

        return $subjectCodes
            ->map(function (string $subjectCode) use ($primaryModules, $compareModules): array {
                /** @var object{subject_code: string, subject: string|null, total: int|string, passed: int|string} $primary */
                $primary = $primaryModules->get($subjectCode);
                /** @var object{subject_code: string, subject: string|null, total: int|string, passed: int|string} $compare */
                $compare = $compareModules->get($subjectCode);

                $primaryRate = $this->rate((int) $primary->passed, (int) $primary->total);
                $compareRate = $this->rate((int) $compare->passed, (int) $compare->total);
                $delta = ($primaryRate !== null && $compareRate !== null)
                    ? round($primaryRate - $compareRate, 1)
                    : null;

                return [
                    'subjectCode' => $subjectCode,
                    'subject' => $primary->subject ?? $compare->subject,
                    'primaryPassRate' => $primaryRate,
                    'comparePassRate' => $compareRate,
                    'delta' => $delta,
                    'trend' => $this->trend($delta),
                ];
            })
            ->values()
            ->all();
    }

    private function rate(int $numerator, int $denominator): ?float
    {
        if ($denominator === 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    /**
     * @return 'improved'|'declined'|'unchanged'
     */
    private function trend(?float $delta): string
    {
        if ($delta === null || abs($delta) < 0.05) {
            return 'unchanged';
        }

        return $delta > 0 ? 'improved' : 'declined';
    }
}
