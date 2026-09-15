<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teaching;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\CourseWorkProgressReportStoreRequest;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Users\User;
use App\Services\AcademicCalendars\CourseWorkProgressReportService;
use App\Services\AcademicCalendars\CourseWorkProgressService;
use App\Services\AcademicCalendars\LecturerInChargeService;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Coursework capture progress per programme: the Lecturer in Charge follows the programmes they lead and
 * reports to the HOD; holders of view:course-work-progress follow the programmes in their departments.
 */
class CourseWorkProgressController extends Controller
{
    public function __construct(
        private readonly CourseWorkProgressService $progress,
        private readonly CourseWorkProgressReportService $reports,
        private readonly LecturerInChargeService $lecturersInCharge,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $ledClassConfigIds = $this->lecturersInCharge->classConfigIdsForUser($user);
        $canViewDepartments = $user->can('view:course-work-progress');

        abort_if($ledClassConfigIds === [] && ! $canViewDepartments, 403);

        $departmentIds = UserAccessScope::for($user)->departmentIds();
        $currentYear = now()->year;

        $visibleProgrammes = fn () => ClassConfig::query()
            ->whereIn('id', AcademicCalendarClass::query()->select('class_config_id'))
            ->where(function ($query) use ($ledClassConfigIds, $canViewDepartments, $departmentIds): void {
                $query->whereIn('id', $ledClassConfigIds === [] ? [-1] : $ledClassConfigIds);

                if ($canViewDepartments) {
                    $departmentIds === null
                        ? $query->orWhereNotNull('id')
                        : $query->orWhereIn('institution_department_id', $departmentIds === [] ? [-1] : $departmentIds);
                }
            });

        // The current year opens by default; earlier years are offered only where the viewer has programmes to look back on.
        $availableYears = $visibleProgrammes()
            ->distinct()
            ->pluck('calendar_year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0 && $year <= $currentYear)
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $requestedYear = $request->integer('calendar_year');
        $calendarYear = in_array($requestedYear, $availableYears, true) ? $requestedYear : $currentYear;

        $classConfigs = $visibleProgrammes()
            ->with(['departmentCourse.course', 'departmentLevel.level', 'modeOfStudy', 'institutionDepartment.department'])
            ->where('calendar_year', (string) $calendarYear)
            ->orderBy('institution_department_id')
            ->orderBy('department_course_id')
            ->limit(200)
            ->get();

        return Inertia::render('teaching/courseWorkProgress/Index', [
            'calendarYear' => $calendarYear,
            'currentYear' => $currentYear,
            'availableYears' => $availableYears,
            'isHistorical' => $calendarYear < $currentYear,
            'programmes' => $classConfigs->map(function (ClassConfig $classConfig) use ($ledClassConfigIds): array {
                $isLecturerInCharge = in_array((int) $classConfig->id, $ledClassConfigIds, true);

                return [
                    'classConfigId' => (int) $classConfig->id,
                    'programme' => $this->progress->programmeLabel($classConfig),
                    'departmentName' => (string) ($classConfig->institutionDepartment?->department?->name ?? ''),
                    'lecturerInCharge' => $this->lecturersInCharge->lecturerInChargeFor($classConfig)['name'] ?? null,
                    'isLecturerInCharge' => $isLecturerInCharge,
                    // Totals are only worked out for programmes the viewer leads, to keep the list quick.
                    'totals' => $isLecturerInCharge ? $this->progress->forClassConfig($classConfig)['totals'] : null,
                ];
            })->values()->all(),
        ]);
    }

    public function show(Request $request, ClassConfig $classConfig): Response
    {
        /** @var User $user */
        $user = $request->user();
        $isLecturerInCharge = $this->lecturersInCharge->isLecturerInCharge($user, (int) $classConfig->id);

        abort_unless(
            $isLecturerInCharge
            || ($user->can('view:course-work-progress')
                && UserAccessScope::for($user)->canReachDepartment((int) $classConfig->institution_department_id)),
            403
        );

        // Earlier years stay viewable for reference, but reports are only sent for the current year.
        $isHistorical = (int) $classConfig->calendar_year < now()->year;

        return Inertia::render('teaching/courseWorkProgress/Show', [
            'progress' => $this->progress->forClassConfig($classConfig),
            'isHistorical' => $isHistorical,
            'canSubmitReport' => $isLecturerInCharge && ! $isHistorical && $user->can('submit:course-work-progress-reports'),
            'reports' => CourseWorkProgressReport::query()
                ->with(['submitter', 'acknowledger'])
                ->where('class_config_id', $classConfig->id)
                ->latest('submitted_at')
                ->limit(10)
                ->get()
                ->map(fn (CourseWorkProgressReport $report): array => [
                    'id' => (int) $report->id,
                    'submittedAt' => $report->submitted_at?->toIso8601String(),
                    'submitterName' => (string) ($report->submitter?->full_name ?? ''),
                    'notes' => $report->notes,
                    'totals' => $report->snapshot['totals'] ?? null,
                    'acknowledgedAt' => $report->acknowledged_at?->toIso8601String(),
                    'acknowledgerName' => (string) ($report->acknowledger?->full_name ?? ''),
                    'hodComment' => $report->hod_comment,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function submit(CourseWorkProgressReportStoreRequest $request, ClassConfig $classConfig): RedirectResponse
    {
        $this->reports->submit($request->user(), $classConfig, $request->validated('notes'));

        return back()->with('success', __('academic_calendar.course_work_progress_submitted'));
    }
}
