<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Helpers\DropdownHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ApplicationExportPreviewRequest;
use App\Http\Requests\Maintenance\ExportApplicationRequest;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Maintenance\ApplicationExportPreviewResource;
use App\Jobs\Applications\ExportApplicationJob;
use App\Queries\Applications\ApplicationExportQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationExportController extends Controller
{
    public function index(
        ApplicationExportPreviewRequest $request,
        ApplicationExportQuery $query,
    ): Response {
        $filters = $request->exportFilters();

        return Inertia::render('maintenance/ApplicationExport', [
            'filters' => $filters,
            'stats' => $query->stats($filters),
            'applications' => ApplicationExportPreviewResource::collection($query->preview($filters)),
            'intakePeriods' => IntakePeriodResource::collection(DropdownHelper::getIntakePeriods()),
        ]);
    }

    public function store(ExportApplicationRequest $request): RedirectResponse
    {
        /** @var list<string> $recipientEmails */
        $recipientEmails = $request->validated('recipient_emails');

        ExportApplicationJob::dispatch($request->exportFilters(), $recipientEmails)->withoutDelay();

        return back()->with('success', __('trans.maintenance_export_application_queued_message'));
    }
}
