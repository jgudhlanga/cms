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
        [$startDate, $endDate] = $this->extractFilters();
        $metrics = new ApplicationMetricsService($startDate, $endDate);
        return response()->json([
            'users' => $metrics->users(),
            'totalApplications' => $metrics->total(),
            'maleApplications' => $metrics->male(),
            'femaleApplications' => $metrics->female(),
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
