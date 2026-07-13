<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Enums\Enrolments\BulkFinaliseEnrolmentAuditEventEnum;
use App\Exceptions\AccountPurge\AccountPurgeArchiveRestoreException;
use App\Exceptions\Maintenance\StudentIdNumberConflictException;
use App\Exports\Maintenance\ApprenticeImportTemplateExport;
use App\Exports\Maintenance\StaffImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\ApprenticeImportPreviewRequest;
use App\Http\Requests\Maintenance\ApprenticeImportProcessRequest;
use App\Http\Requests\Maintenance\ApprenticeImportRefreshRowRequest;
use App\Http\Requests\Maintenance\DispatchBulkFinaliseEnrolmentsRequest;
use App\Http\Requests\Maintenance\ExportApplicationRequest;
use App\Http\Requests\Maintenance\ExportStudentEnrollmentRequest;
use App\Http\Requests\Maintenance\FixStudentIdNumberRequest;
use App\Http\Requests\Maintenance\MaintenanceUserBulkPurgeRequest;
use App\Http\Requests\Maintenance\MaintenanceUserPurgeRequest;
use App\Http\Requests\Maintenance\MergeStudentAccountsRequest;
use App\Http\Requests\Maintenance\RejectMergePreviewApplicationRequest;
use App\Http\Requests\Maintenance\StaffImportCreateLookupRequest;
use App\Http\Requests\Maintenance\StaffImportPreviewRequest;
use App\Http\Requests\Maintenance\StaffImportProcessRequest;
use App\Http\Resources\Maintenance\AccountPurgeArchiveResource;
use App\Http\Resources\Maintenance\FaultyStudentIdNumberResource;
use App\Http\Resources\Maintenance\NonEnrolledStudentUserResource;
use App\Http\Resources\Maintenance\StudentAccountMergePreviewResource;
use App\Http\Resources\Maintenance\VerifiedStudentForFinalEnrolmentResource;
use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\BulkFinaliseEnrolmentsJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\AccountPurge\AccountPurgeArchiveFlushService;
use App\Services\AccountPurge\AccountPurgeArchiveRestoreService;
use App\Services\AccountPurge\AccountPurgeArchivesListService;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Enrolments\BulkFinaliseEnrolmentAuditLogger;
use App\Services\Enrolments\BulkFinaliseEnrolmentsService;
use App\Services\Enrolments\StudentBankPaymentMatcher;
use App\Services\Maintenance\Staff\StaffImportLookupCreator;
use App\Services\Maintenance\Staff\StaffImportService;
use App\Services\Maintenance\Staff\StaffImportTemplateService;
use App\Services\Maintenance\Students\ApprenticeImportService;
use App\Services\Maintenance\Students\ApprenticeImportTemplateService;
use App\Services\Maintenance\Students\FaultyStudentIdNumbersService;
use App\Services\Maintenance\Students\FixStudentIdNumberService;
use App\Services\Maintenance\Students\MaintenanceExportCountsService;
use App\Services\Maintenance\Students\RejectStudentApplicationService;
use App\Services\Maintenance\Students\StudentAccountMergePreviewService;
use App\Services\Maintenance\Students\StudentAccountMergeService;
use App\Services\Maintenance\Students\VerifiedStudentsForFinalEnrolmentService;
use App\Services\Maintenance\Users\MaintenanceUserPurgeService;
use App\Services\Maintenance\Users\NonEnrolledStudentUsersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    public function accountPurgeArchives(
        AccountPurgeArchivesListService $service,
    ): AnonymousResourceCollection {
        return AccountPurgeArchiveResource::collection(
            $service->paginate(
                $this->resolveTenantId(),
                request()->only(['search', 'purge_type', 'status']),
            ),
        );
    }

    public function restoreAccountPurgeArchive(
        AccountPurgeArchive $archive,
        AccountPurgeArchiveRestoreService $restoreService,
    ): JsonResponse {
        $this->assertArchiveTenant($archive);

        try {
            $result = $restoreService->restore($archive);
        } catch (AccountPurgeArchiveRestoreException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errorCode' => $exception->errorCode,
            ], 422);
        }

        return response()->json([
            'message' => __('trans.maintenance_archives_restore_success'),
            'data' => $result,
        ]);
    }

    public function flushAccountPurgeArchive(
        AccountPurgeArchive $archive,
        AccountPurgeArchiveFlushService $flushService,
    ): HttpResponse {
        $this->assertArchiveTenant($archive);

        if (! $archive->isFlushable()) {
            throw ValidationException::withMessages([
                'archive' => [__('trans.maintenance_archives_flush_not_allowed')],
            ]);
        }

        $flushService->flushArchive($archive);

        return response()->noContent();
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
        $authUser = Auth::user();
        abort_if($authUser === null, 403);

        $purgeService->purge(
            $user,
            $authUser,
            $request->validated('reason'),
            $this->resolveTenantId(),
        );

        return response()->noContent();
    }

    public function bulkPurgeNonEnrolledStudentUsers(
        MaintenanceUserBulkPurgeRequest $request,
        MaintenanceUserPurgeService $purgeService,
    ): JsonResponse {
        $authUser = Auth::user();
        abort_if($authUser === null, 403);

        /** @var list<int> $userIds */
        $userIds = array_map('intval', $request->validated('user_ids'));

        $result = $purgeService->purgeMany(
            $userIds,
            $authUser,
            $request->validated('reason'),
            $this->resolveTenantId(),
        );

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

    public function createStaffImportLookup(
        StaffImportCreateLookupRequest $request,
        StaffImportLookupCreator $lookupCreator,
    ): JsonResponse {
        $validated = $request->validated();

        $lookup = $lookupCreator->create(
            $this->resolveTenantId(),
            (string) $validated['type'],
            (string) $validated['name'],
        );

        return response()->json($lookup);
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

    public function apprenticeManagement(): Response
    {
        return Inertia::render('maintenance/ApprenticeManager', [
            'calendarYear' => (int) now()->format('Y'),
        ]);
    }

    public function downloadApprenticeImportTemplate(
        ApprenticeImportTemplateService $templateService,
    ): BinaryFileResponse {
        $data = $templateService->assemble();

        return Excel::download(
            new ApprenticeImportTemplateExport($data),
            $templateService->downloadFileName(),
        );
    }

    public function previewApprenticeImport(
        ApprenticeImportPreviewRequest $request,
        ApprenticeImportService $importService,
    ): JsonResponse {
        $validated = $request->validated();
        $file = $request->file('file');

        if ($file === null) {
            abort(422);
        }

        $preview = $importService->preview(
            $file,
            (int) $validated['institution_department_id'],
            (int) $validated['calendar_year'],
        );

        return response()->json($preview);
    }

    public function processApprenticeImport(
        ApprenticeImportProcessRequest $request,
        ApprenticeImportService $importService,
    ): JsonResponse {
        $validated = $request->validated();

        /** @var list<array{rowNumber: int, studentApplicationId: int, apprenticeNumber?: string|null, employer?: string|null}> $rows */
        $rows = $validated['rows'];

        $result = $importService->process(
            $rows,
            (int) $validated['institution_department_id'],
            (int) $validated['calendar_year'],
        );

        return response()->json($result);
    }

    public function refreshApprenticeImportRow(
        ApprenticeImportRefreshRowRequest $request,
        ApprenticeImportService $importService,
    ): JsonResponse {
        $validated = $request->validated();

        $parsedRow = [
            'rowNumber' => (int) $validated['rowNumber'],
            'idNumber' => $validated['idNumber'] ?? null,
            'studentNumber' => $validated['studentNumber'] ?? null,
            'apprenticeNumber' => $validated['apprenticeNumber'] ?? null,
            'employer' => $validated['employer'] ?? null,
        ];

        $result = $importService->refreshPreviewRow(
            $parsedRow,
            (int) $validated['institution_department_id'],
            (int) $validated['calendar_year'],
        );

        return response()->json($result);
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
                    'mergeUrl' => route('maintenance.faulty-student-ids.merge', [
                        'student' => $student->id,
                        'target' => $exception->conflictingStudentId,
                        'id_number' => $exception->idNumber,
                    ]),
                ],
            ], 409);
        }

        return response()->json([
            'data' => FaultyStudentIdNumberResource::make($student),
        ]);
    }

    public function verifiedStudentsFinalEnrolment(
        StudentBankPaymentMatcher $paymentMatcher,
    ): Response {
        ['start_date' => $startDate, 'end_date' => $endDate] = $paymentMatcher->resolveDefaultDateRange();

        return Inertia::render('maintenance/VerifiedStudentsFinalEnrolment', [
            'paymentWindow' => [
                'startDate' => $startDate->toDateTimeString(),
                'endDate' => $endDate->toDateTimeString(),
            ],
        ]);
    }

    public function verifiedStudentsFinalEnrolmentData(
        VerifiedStudentsForFinalEnrolmentService $service,
    ): AnonymousResourceCollection {
        $filters = request()->only(['search', 'department', 'level', 'course', 'payment_status']);
        $meta = $service->resolveBasicMeta($filters);

        return VerifiedStudentForFinalEnrolmentResource::collection(
            $service->paginate($filters),
        )->additional([
            'paymentWindow' => [
                'startDate' => $meta['startDate'],
                'endDate' => $meta['endDate'],
            ],
            'summary' => $meta['summary'],
        ]);
    }

    public function verifiedStudentsFinalEnrolmentSummary(
        VerifiedStudentsForFinalEnrolmentService $service,
    ): JsonResponse {
        $filters = request()->only(['search', 'department', 'level', 'course', 'payment_status']);
        $meta = $service->resolvePaymentSummary($filters);

        return response()->json([
            'paymentWindow' => [
                'startDate' => $meta['startDate'],
                'endDate' => $meta['endDate'],
            ],
            'summary' => $meta['summary'],
        ]);
    }

    public function dispatchBulkFinaliseEnrolments(
        DispatchBulkFinaliseEnrolmentsRequest $request,
        BulkFinaliseEnrolmentsService $bulkFinaliseService,
        BulkFinaliseEnrolmentAuditLogger $auditLogger,
        StudentBankPaymentMatcher $paymentMatcher,
    ): JsonResponse {
        if ($bulkFinaliseService->isRunActive()) {
            return response()->json([
                'message' => __('trans.maintenance_verified_students_final_enrolment_run_already_active'),
            ], 409);
        }

        ['start_date' => $startDate, 'end_date' => $endDate] = $paymentMatcher->resolveDefaultDateRange();
        $runId = (string) Str::uuid();
        $studentApplicationIds = $request->studentApplicationIds();
        $forceFinalise = $request->forceFinalise();
        $initiatedByUserId = auth()->id();

        if (! $bulkFinaliseService->acquireActiveRun($runId)) {
            return response()->json([
                'message' => __('trans.maintenance_verified_students_final_enrolment_run_already_active'),
            ], 409);
        }

        $bulkFinaliseService->writeRunProgress($runId, [
            'status' => 'pending',
            'processed' => 0,
            'total' => $bulkFinaliseService->loadVerifiedStudentApplications($studentApplicationIds)->count(),
            'successful' => 0,
            'failed' => 0,
            'message' => null,
        ]);

        $auditLogger->log(
            runId: $runId,
            event: BulkFinaliseEnrolmentAuditEventEnum::RunStarted,
            userId: is_int($initiatedByUserId) ? $initiatedByUserId : null,
            forceFinalise: $forceFinalise,
            metadata: [
                'student_application_ids' => $studentApplicationIds,
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
            ],
        );

        BulkFinaliseEnrolmentsJob::dispatch(
            runId: $runId,
            startDate: $startDate->toDateTimeString(),
            endDate: $endDate->toDateTimeString(),
            initiatedByUserId: is_int($initiatedByUserId) ? $initiatedByUserId : null,
            studentApplicationIds: $studentApplicationIds,
            forceFinalise: $forceFinalise,
        )->withoutDelay();

        return response()->json([
            'runId' => $runId,
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'message' => __('trans.maintenance_verified_students_final_enrolment_run_queued', [
                'start' => $startDate->toDateTimeString(),
                'end' => $endDate->toDateTimeString(),
            ]),
        ]);
    }

    public function bulkFinaliseEnrolmentsRunStatus(
        string $runId,
        BulkFinaliseEnrolmentsService $bulkFinaliseService,
    ): JsonResponse {
        $progress = $bulkFinaliseService->getRunProgress($runId);

        if ($progress === null) {
            abort(404);
        }

        return response()->json($progress);
    }

    public function mergeFaultyStudentPreviewData(
        Student $student,
        StudentAccountMergePreviewService $previewService,
    ): JsonResponse {
        $targetId = (int) request()->query('target', 0);
        $idNumber = EnrollmentLookupService::normalizeNationalId((string) request()->query('id_number', ''));

        try {
            $preview = $previewService->build($student, $targetId, $idNumber);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('trans.maintenance_faulty_data_merge_failure');

            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'data' => StudentAccountMergePreviewResource::make($preview)->resolve(),
        ]);
    }

    public function mergeFaultyStudentPreview(
        Student $student,
        StudentAccountMergePreviewService $previewService,
    ): Response|RedirectResponse {
        $targetId = (int) request()->query('target', 0);
        $idNumber = EnrollmentLookupService::normalizeNationalId((string) request()->query('id_number', ''));

        try {
            $preview = $previewService->build($student, $targetId, $idNumber);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('trans.maintenance_faulty_data_merge_failure');

            return redirect()
                ->route('maintenance.faulty-student-ids')
                ->with('error', $message);
        }

        return Inertia::render('maintenance/FaultyStudentIdMerge', [
            'preview' => StudentAccountMergePreviewResource::make($preview)->resolve(),
        ]);
    }

    public function rejectMergePreviewApplication(
        RejectMergePreviewApplicationRequest $request,
        StudentApplication $studentApplication,
        RejectStudentApplicationService $rejectService,
    ): RedirectResponse {
        $rejectService->reject($studentApplication);

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
        $validated = $request->validated();
        $previewToken = (string) $validated['preview_token'];
        $rowCorrections = isset($validated['row_corrections']) && is_array($validated['row_corrections'])
            ? $validated['row_corrections']
            : null;
        $excludedRowNumbers = isset($validated['excluded_row_numbers']) && is_array($validated['excluded_row_numbers'])
            ? $validated['excluded_row_numbers']
            : null;
        $result = $importService->processFromPreview(
            $this->resolveTenantId(),
            $previewToken,
            $rowCorrections,
            $excludedRowNumbers,
        );

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
            'programmesCount' => $survivor->applications()->count(),
            'enrolmentsCount' => $survivor->enrolments()->count(),
        ];
    }

    private function assertArchiveTenant(AccountPurgeArchive $archive): void
    {
        if ($archive->tenant_id !== $this->resolveTenantId()) {
            abort(403);
        }
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
