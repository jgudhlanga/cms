<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Exceptions\Maintenance\StudentIdNumberConflictException;
use App\Exports\Maintenance\StaffImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ExportApplicationRequest;
use App\Http\Requests\Maintenance\ExportStudentEnrollmentRequest;
use App\Http\Requests\Maintenance\FixStudentIdNumberRequest;
use App\Http\Requests\Maintenance\MaintenanceUserBulkPurgeRequest;
use App\Http\Requests\Maintenance\MaintenanceUserPurgeRequest;
use App\Http\Requests\Maintenance\MergeStudentAccountsRequest;
use App\Http\Requests\Maintenance\RejectMergePreviewApplicationRequest;
use App\Http\Requests\Maintenance\StaffImportPreviewRequest;
use App\Http\Requests\Maintenance\StaffImportProcessRequest;
use App\Http\Resources\Maintenance\FaultyStudentIdNumberResource;
use App\Http\Resources\Maintenance\NonEnrolledStudentUserResource;
use App\Http\Resources\Maintenance\StudentAccountMergePreviewResource;
use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use App\Services\Maintenance\FaultyStudentIdNumbersService;
use App\Services\Maintenance\MaintenanceExportCountsService;
use App\Services\Maintenance\FixStudentIdNumberService;
use App\Services\Maintenance\MaintenanceUserPurgeService;
use App\Services\Maintenance\NonEnrolledStudentUsersService;
use App\Services\Maintenance\StaffImportService;
use App\Services\Maintenance\StaffImportTemplateService;
use App\Services\Maintenance\RejectStudentProgramApplicationService;
use App\Services\Maintenance\StudentAccountMergePreviewService;
use App\Services\Maintenance\StudentAccountMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceController extends Controller
{
    public function index(MaintenanceExportCountsService $exportCountsService): Response
    {
        return Inertia::render('maintenance/Index', [
            'staffImportResult' => session('staffImportResult'),
            'exportCounts' => $exportCountsService->resolve(),
        ]);
    }

    public function exportCounts(MaintenanceExportCountsService $exportCountsService): JsonResponse
    {
        $intakeYear = request()->query('intake_year');
        $intakeYear = is_string($intakeYear) && $intakeYear !== '' ? $intakeYear : null;

        return response()->json($exportCountsService->resolve($intakeYear));
    }

    public function nonEnrolledStudentUsers(
        NonEnrolledStudentUsersService $service,
    ): AnonymousResourceCollection {
        return NonEnrolledStudentUserResource::collection(
            $service->paginate(
                $this->resolveTenantId(),
                request()->only(['search', 'application_status']),
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

    public function faultyStudentIds(): Response
    {
        return Inertia::render('maintenance/FaultyStudentIds', [
            'mergeResult' => session('mergeResult'),
        ]);
    }

    public function faultyStudentIdNumbers(
        FaultyStudentIdNumbersService $service,
    ): AnonymousResourceCollection {
        return FaultyStudentIdNumberResource::collection(
            $service->paginate(request()->only(['search'])),
        );
    }

    public function fixFaultyStudentIdNumber(
        FixStudentIdNumberRequest $request,
        Student $student,
        FixStudentIdNumberService $fixService,
    ): JsonResponse {
        try {
            $student = $fixService->fix($student, (string) $request->validated('id_number'));
        } catch (StudentIdNumberConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'conflict' => [
                    'conflictingStudentId' => $exception->conflictingStudentId,
                    'idNumber' => $exception->idNumber,
                ],
            ], 409);
        }

        return response()->json([
            'data' => FaultyStudentIdNumberResource::make($student),
        ]);
    }

    public function mergeFaultyStudentPreview(
        Student $student,
        StudentAccountMergePreviewService $previewService,
    ): Response {
        $targetId = (int) request()->query('target', 0);
        $idNumber = (string) request()->query('id_number', '');

        $preview = $previewService->build($student, $targetId, $idNumber);

        return Inertia::render('maintenance/FaultyStudentIdMerge', [
            'preview' => StudentAccountMergePreviewResource::make($preview)->resolve(),
        ]);
    }

    public function rejectMergePreviewApplication(
        RejectMergePreviewApplicationRequest $request,
        StudentProgram $studentProgram,
        RejectStudentProgramApplicationService $rejectService,
    ): RedirectResponse {
        $rejectService->reject($studentProgram);

        return redirect()
            ->back()
            ->with('success', __('trans.maintenance_faulty_data_merge_reject_success'));
    }

    public function mergeFaultyStudentAccounts(
        MergeStudentAccountsRequest $request,
        StudentAccountMergeService $mergeService,
    ): RedirectResponse {
        $survivor = $mergeService->merge(
            (int) $request->validated('source_student_id'),
            (int) $request->validated('target_student_id'),
            (int) $request->validated('survivor_student_id'),
            (string) $request->validated('id_number'),
        );

        return redirect()
            ->route('maintenance.faulty-student-ids')
            ->with('success', __('trans.maintenance_faulty_data_merge_success'))
            ->with('mergeResult', $this->mergeResultPayload($survivor));
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

    /**
     * @return array<string, mixed>
     */
    private function mergeResultPayload(Student $survivor): array
    {
        $user = $survivor->user;

        return [
            'studentId' => $survivor->id,
            'userId' => $survivor->user_id,
            'name' => $user?->full_name,
            'email' => $user?->email,
            'phoneNumber' => $user?->phone_number,
            'studentNumber' => $survivor->student_number,
            'idNumber' => $survivor->id_number,
            'programmesCount' => $survivor->programs()->count(),
            'enrolmentsCount' => $survivor->enrolments()->count(),
        ];
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
