<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\CourseWorkProgressAcknowledgeRequest;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Users\User;
use App\Services\AcademicCalendars\CourseWorkProgressReportService;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseWorkProgressReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CourseWorkProgressReport::class);

        /** @var User $user */
        $user = $request->user();
        $departmentIds = UserAccessScope::for($user)->departmentIds();
        $status = $request->query('status') === 'all' ? 'all' : 'awaiting';

        $reports = CourseWorkProgressReport::query()
            ->with(['submitter', 'acknowledger', 'institutionDepartment.department'])
            ->when($departmentIds !== null, fn ($query) => $query->whereIn('institution_department_id', $departmentIds === [] ? [-1] : $departmentIds))
            ->when($status === 'awaiting', fn ($query) => $query->whereNull('acknowledged_at'))
            ->latest('submitted_at')
            ->limit(200)
            ->get();

        return Inertia::render('institution/courseWorkProgressReports/Index', [
            'status' => $status,
            'reports' => $reports->map(fn (CourseWorkProgressReport $report): array => [
                'id' => (int) $report->id,
                'programme' => (string) ($report->snapshot['programme'] ?? ''),
                'departmentName' => (string) ($report->institutionDepartment?->department?->name ?? ''),
                'submitterName' => (string) ($report->submitter?->full_name ?? ''),
                'submittedAt' => $report->submitted_at?->toIso8601String(),
                'notes' => $report->notes,
                'totals' => $report->snapshot['totals'] ?? null,
                'acknowledgedAt' => $report->acknowledged_at?->toIso8601String(),
                'acknowledgerName' => (string) ($report->acknowledger?->full_name ?? ''),
                'hodComment' => $report->hod_comment,
                'progressUrl' => route('teaching.course-work-progress.show', ['class_config' => $report->class_config_id]),
                'can' => [
                    'acknowledge' => $report->acknowledged_at === null && $user->can('acknowledge', $report),
                ],
            ])->values()->all(),
        ]);
    }

    public function acknowledge(
        CourseWorkProgressAcknowledgeRequest $request,
        CourseWorkProgressReport $courseWorkProgressReport,
        CourseWorkProgressReportService $reports,
    ): RedirectResponse {
        $reports->acknowledge($request->user(), $courseWorkProgressReport, $request->validated('comment'));

        return back()->with('success', __('academic_calendar.course_work_progress_acknowledged'));
    }
}
