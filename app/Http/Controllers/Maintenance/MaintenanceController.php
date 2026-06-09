<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Exports\Maintenance\StaffImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ExportApplicationRequest;
use App\Http\Requests\Maintenance\ExportStudentEnrollmentRequest;
use App\Http\Requests\Maintenance\MaintenanceUserBulkPurgeRequest;
use App\Http\Requests\Maintenance\MaintenanceUserPurgeRequest;
use App\Http\Requests\Maintenance\StaffImportPreviewRequest;
use App\Http\Requests\Maintenance\StaffImportProcessRequest;
use App\Http\Resources\Maintenance\NonEnrolledStudentUserResource;
use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Models\Users\User;
use App\Services\Maintenance\MaintenanceUserPurgeService;
use App\Services\Maintenance\NonEnrolledStudentUsersService;
use App\Services\Maintenance\StaffImportService;
use App\Services\Maintenance\StaffImportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('maintenance/Index', [
            'staffImportResult' => session('staffImportResult'),
        ]);
    }

    public function nonEnrolledStudentUsers(
        NonEnrolledStudentUsersService $service,
    ): AnonymousResourceCollection {
        return NonEnrolledStudentUserResource::collection(
            $service->paginate(
                $this->resolveTenantId(),
                request()->only(['search']),
            ),
        );
    }

    public function purgeNonEnrolledStudentUser(
        MaintenanceUserPurgeRequest $request,
        User $user,
        MaintenanceUserPurgeService $purgeService,
    ): HttpResponse {
        $purgeService->purge($user, $this->resolveTenantId());

        return response()->noContent();
    }

    public function bulkPurgeNonEnrolledStudentUsers(
        MaintenanceUserBulkPurgeRequest $request,
        MaintenanceUserPurgeService $purgeService,
    ): JsonResponse {
        /** @var list<int> $userIds */
        $userIds = array_map('intval', $request->validated('user_ids'));

        $result = $purgeService->purgeMany($userIds, $this->resolveTenantId());

        return response()->json($result);
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

    public function downloadStaffImportTemplate(StaffImportTemplateService $templateService): BinaryFileResponse
    {
        $tenantId = $this->resolveTenantId();
        $data = $templateService->assemble($tenantId);

        return Excel::download(
            new StaffImportTemplateExport($data),
            $templateService->downloadFileName(),
        );
    }

    public function previewStaffImport(
        StaffImportPreviewRequest $request,
        StaffImportService $importService,
    ): JsonResponse {
        $file = $request->file('file');

        if ($file === null) {
            abort(422);
        }

        $preview = $importService->preview($this->resolveTenantId(), $file);

        return response()->json($preview);
    }

    public function processStaffImport(
        StaffImportProcessRequest $request,
        StaffImportService $importService,
    ): RedirectResponse {
        $previewToken = (string) $request->validated('preview_token');
        $result = $importService->processFromPreview($this->resolveTenantId(), $previewToken);

        return redirect()
            ->route('maintenance.index')
            ->with('staffImportResult', $result)
            ->with(
                'success',
                __('trans.maintenance_staff_import_success', [
                    'succeeded' => $result['rowsSucceeded'],
                    'failed' => $result['rowsFailed'],
                    'skipped' => $result['rowsSkipped'],
                ]),
            );
    }

    private function resolveTenantId(): int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return (int) $user->tenant_id;
    }
}
