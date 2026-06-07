<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ExportApplicationRequest;
use App\Http\Requests\Maintenance\ExportStudentEnrollmentRequest;
use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('maintenance/Index');
    }

    public function exportStudentEnrollment(ExportStudentEnrollmentRequest $request)
    {
        $intakeYear = $request->validated('intake_year');
        $intakeYear = is_string($intakeYear) && $intakeYear !== '' ? $intakeYear : null;

        /** @var list<string> $recipientEmails */
        $recipientEmails = $request->validated('recipient_emails');

        ExportStudentEnrollmentJob::dispatch($intakeYear, $recipientEmails)->withoutDelay();

        return back()->with(
            'success',
            __('trans.maintenance_export_queued_message'),
        );
    }

    public function exportApplication(ExportApplicationRequest $request)
    {
        $intakeYear = $request->validated('intake_year');
        $intakeYear = is_string($intakeYear) && $intakeYear !== '' ? $intakeYear : null;

        /** @var list<string> $recipientEmails */
        $recipientEmails = $request->validated('recipient_emails');

        ExportApplicationJob::dispatch($intakeYear, $recipientEmails)->withoutDelay();

        return back()->with(
            'success',
            __('trans.maintenance_export_application_queued_message'),
        );
    }
}
