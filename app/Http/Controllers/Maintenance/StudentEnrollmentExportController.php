<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ExportStudentEnrollmentRequest;
use App\Http\Requests\Maintenance\StudentEnrollmentExportPreviewRequest;
use App\Http\Resources\Maintenance\StudentEnrolmentExportPreviewResource;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Queries\Enrolments\StudentEnrollmentExportQuery;
use App\Services\Maintenance\Students\MaintenanceExportOptionsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentExportController extends Controller
{
    public function index(
        StudentEnrollmentExportPreviewRequest $request,
        StudentEnrollmentExportQuery $query,
        MaintenanceExportOptionsService $options,
    ): Response {
        $filters = $request->exportFilters();

        return Inertia::render('maintenance/StudentEnrollmentExport', [
            'filters' => $filters,
            'stats' => $query->stats($filters),
            'enrolments' => StudentEnrolmentExportPreviewResource::collection($query->preview($filters)),
            'calendarYears' => $options->calendarYears(),
            'semesters' => $options->semesters(),
            'calendarTypes' => $options->calendarTypes(),
        ]);
    }

    public function store(ExportStudentEnrollmentRequest $request): RedirectResponse
    {
        /** @var list<string> $recipientEmails */
        $recipientEmails = $request->validated('recipient_emails');

        ExportStudentEnrollmentJob::dispatch($request->exportFilters(), $recipientEmails)->withoutDelay();

        return back()->with('success', __('trans.maintenance_export_queued_message'));
    }
}
