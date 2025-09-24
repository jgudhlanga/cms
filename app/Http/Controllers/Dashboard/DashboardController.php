<?php

namespace App\Http\Controllers\Dashboard;

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

    public function __invoke()
    {
        $this->authorize('viewDashboard');
        [$intakePeriodId] = $this->extractFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $metrics = new ApplicationMetricsService($intakePeriod->id);
        return Inertia::render('dashboard/Index', [
            'users' => $metrics->users(),
            'totalApplications' => $metrics->total(),
            'maleApplications' => $metrics->male(),
            'femaleApplications' => $metrics->female(),
        ]);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        return [$intakePeriodId];
    }

    private function resolveIntakePeriod(?int $intakePeriodId)
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

    private function totalApplications()
    {
        [$intakePeriodId] = $this->extractFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        return StudentProgram::where('intake_period_id', $intakePeriod->id)->count();
    }

    private function maleApplications()
    {
        [$intakePeriodId] = $this->extractFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $gender = Gender::where('title', GenderEnum::MALE->label())->first();
        return StudentProgram::join('students', 'students.id', '=', 'student_programs.student_id')
            ->where('student_programs.intake_period_id', $intakePeriod->id)
            ->where('students.gender_id', $gender->id)
            ->count();
    }
}
