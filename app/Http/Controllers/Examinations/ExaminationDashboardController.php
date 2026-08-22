<?php

declare(strict_types=1);

namespace App\Http\Controllers\Examinations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Examinations\ExaminationDashboardRequest;
use App\Services\Examinations\ExaminationDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationDashboardController extends Controller
{
    public function __invoke(
        ExaminationDashboardRequest $request,
        ExaminationDashboardService $dashboardService,
    ): Response {
        return Inertia::render(
            'examinations/Dashboard',
            $dashboardService->pagePayload($request->filters()),
        );
    }
}
