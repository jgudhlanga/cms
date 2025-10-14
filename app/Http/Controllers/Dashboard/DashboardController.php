<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Enrolments\DailyDistributionResource;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
use App\Http\Resources\Enrolments\LevelDistributionResource;
use App\Services\ApplicationMetricsService;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public function __construct(protected ApplicationMetricsService $metricsService)
    {
    }

    public function __invoke()
    {
        $this->authorize('viewDashboard');
        $departmentDistribution = DepartmentDistributionResource::collection($this->metricsService->applicationsByDepartment());
        $levelDistribution = LevelDistributionResource::collection($this->metricsService->applicationsByLevel());
        $dailyDistribution = DailyDistributionResource::collection($this->metricsService->getDailyCountStats());
        return Inertia::render('dashboard/Index', compact('departmentDistribution', 'levelDistribution', 'dailyDistribution'));
    }

}
