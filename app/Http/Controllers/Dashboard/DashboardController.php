<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Enrolments\DailyDistributionResource;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
use App\Http\Resources\Enrolments\LevelDistributionResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\IntakePeriod;
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
        $intakePeriodList = DropdownHelper::getIntakePeriods();
        $intakePeriods = IntakePeriodResource::collection($intakePeriodList);
        $intakePeriod = IntakePeriodResource::make(Helper::resolveIntakePeriod());
        $departmentDistribution = DepartmentDistributionResource::collection($this->metricsService->applicationsByDepartment());
        $levelDistribution = LevelDistributionResource::collection($this->metricsService->applicationsByLevel());
        $dailyDistribution = DailyDistributionResource::collection($this->metricsService->getDailyCountStats());
        return Inertia::render('dashboard/Index',
            compact('departmentDistribution', 'levelDistribution', 'dailyDistribution', 'intakePeriod', 'intakePeriods'));
    }

}
