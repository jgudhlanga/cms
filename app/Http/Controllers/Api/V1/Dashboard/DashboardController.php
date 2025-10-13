<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\GenderEnum;
use App\Http\Controllers\Controller;
use App\Models\Institution\IntakePeriod;
use App\Models\Shared\Gender;
use App\Models\Students\StudentProgram;
use App\Services\ApplicationMetricsService;
use Inertia\Inertia;

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
