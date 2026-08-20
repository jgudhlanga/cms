<?php

declare(strict_types=1);

namespace App\Http\Controllers\Examinations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Examinations\ExaminationDashboardRequest;
use App\Queries\Examinations\ExaminationResultQuery;
use App\Services\Examinations\ExaminationDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationDashboardController extends Controller
{
    public function __invoke(
        ExaminationDashboardRequest $request,
        ExaminationResultQuery $query,
        ExaminationDashboardService $dashboardService,
    ): Response {
        $filters = $query->resolveFilters($request->filters());
        $dashboard = $dashboardService->build($filters);

        $sessionOptions = $query->sessionOptions();
        $compareSessionOptions = array_values(array_filter(
            $sessionOptions,
            fn (array $option): bool => $option['value'] !== ($filters['session'] ?? null),
        ));

        return Inertia::render('examinations/Dashboard', [
            'filters' => [
                'session' => $filters['session'],
                'discipline' => $filters['discipline'],
                'subject_code' => $filters['subject_code'],
                'compare_session' => $filters['compare_session'],
            ],
            'filterOptions' => [
                'sessions' => $sessionOptions,
                'disciplines' => $query->disciplineOptions($filters['session']),
                'subjects' => $query->subjectOptions($filters['session'], $filters['discipline']),
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
        ]);
    }
}
