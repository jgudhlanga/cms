<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
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
        return Inertia::render('dashboard/Index', compact('departmentDistribution'));
    }

}
