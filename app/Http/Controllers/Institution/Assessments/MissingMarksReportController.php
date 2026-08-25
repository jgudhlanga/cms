<?php

namespace App\Http\Controllers\Institution\Assessments;

use App\Exports\Assessments\MissingMarksReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assessments\MissingMarksEscalateRequest;
use App\Http\Requests\Assessments\MissingMarksRemindRequest;
use App\Http\Requests\Assessments\MissingMarksReportIndexRequest;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Services\Assessments\MissingMarksNotificationService;
use App\Services\Assessments\MissingMarksReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MissingMarksReportController extends Controller
{
    public function index(MissingMarksReportIndexRequest $request, MissingMarksReportService $reportService): Response
    {
        return Inertia::render('institution/assessments/MissingMarksReport', $reportService->page($request->validated()));
    }

    public function export(
        MissingMarksReportIndexRequest $request,
        MissingMarksReportService $reportService,
    ): BinaryFileResponse {
        abort_unless($request->user()?->can('export:missing-marks-report'), 403);

        $rows = $reportService->rows($request->validated());
        $exportRows = [
            [
                __('trans.assessment_type'),
                __('trans.class'),
                __('trans.module'),
                __('assessments.missing_marks_lecturer'),
                __('dashboard.academic_incomplete'),
                __('assessments.missing_marks_due_date'),
                __('assessments.missing_marks_last_tier'),
            ],
        ];

        foreach ($rows as $row) {
            $exportRows[] = [
                $row['assessmentTypeName'],
                $row['className'],
                $row['moduleName'],
                $row['lecturerNames'],
                $row['incompleteCount'],
                $row['dueDate'],
                $row['lastTierLabel'] ?? __('assessments.missing_marks_none'),
            ];
        }

        return Excel::download(new MissingMarksReportExport($exportRows), 'missing-marks-report.xlsx');
    }

    public function escalate(
        MissingMarksEscalateRequest $request,
        MissingMarksNotificationService $notificationService,
    ): RedirectResponse {
        $calendar = AssessmentCalendar::query()->findOrFail($request->integer('assessment_calendar_id'));

        if ($notificationService->hasEscalated($calendar)) {
            return back()->with('error', __('assessments.missing_marks_already_escalated'));
        }

        $sent = $notificationService->escalateToPrincipal(
            $calendar,
            $request->user(),
            $request->validated('notes'),
        );

        if (! $sent) {
            return back()->with('error', __('assessments.missing_marks_escalate_failed'));
        }

        return back()->with('success', __('assessments.missing_marks_escalated'));
    }

    public function remind(
        MissingMarksRemindRequest $request,
        MissingMarksNotificationService $notificationService,
    ): RedirectResponse {
        $calendar = AssessmentCalendar::query()->findOrFail($request->integer('assessment_calendar_id'));

        if (! $notificationService->remindLecturers($calendar)) {
            return back()->with('error', __('assessments.missing_marks_remind_failed'));
        }

        return back()->with('success', __('assessments.missing_marks_reminded'));
    }
}
