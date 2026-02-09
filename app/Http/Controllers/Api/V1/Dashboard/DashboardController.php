<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ApplicationMetricsService;

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = new ApplicationMetricsService();
        $departmentDistribution = $metrics->applicationsByDepartment();
        return response()->json([
            'departmentDistribution' => $departmentDistribution,
        ]);
    }

    private function extractFilters(): array
    {
        $dateRange = request()->has('date_range') ? request('date_range') : null;
        $startDate = $dateRange[0] ?? null;
        $endDate = $dateRange[1] ?? null;
        return [$startDate, $endDate];
    }

}
