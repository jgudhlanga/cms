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
        $this->authorize('viewDashboard');
        [$intakePeriodId] = $this->extractFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $metrics = new ApplicationMetricsService($intakePeriod->id);
        return response()->json([
            'users' => $metrics->users(),
            'totalApplications' => $metrics->total(),
            'maleApplications' => $metrics->male(),
            'femaleApplications' => $metrics->female(),
        ]);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $dateRange  = request()->has('date_range') ? request('date_range') : null;
        dd($dateRange);
        return [$intakePeriodId];
    }

    private function resolveIntakePeriod(?int $intakePeriodId)
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

}
