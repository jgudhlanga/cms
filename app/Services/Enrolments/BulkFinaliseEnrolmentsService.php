<?php

declare(strict_types=1);

namespace App\Services\Enrolments;

use App\DataTransferObjects\Enrolments\BulkFinaliseEnrolmentsResult;
use App\Enums\Acl\RoleEnum;
use App\Enums\Enrolments\BulkFinaliseEnrolmentAuditEventEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Exports\Enrolments\BulkFinaliseDryRunExport;
use App\Exports\Enrolments\BulkFinaliseFailuresExport;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Queries\Maintenance\VerifiedStudentsForFinalEnrolmentQuery;
use App\Services\Maintenance\Students\VerifiedStudentsForFinalEnrolmentService;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BulkFinaliseEnrolmentsService
{
    public const RUN_CACHE_PREFIX = 'bulk-finalise-run:';

    public const ACTIVE_RUN_LOCK_KEY = 'bulk-finalise-active';

    private function runCacheTtlSeconds(): int
    {
        return (int) config('custom.enrolments.bulk_finalise.run_cache_ttl_seconds', 7200);
    }

    public function __construct(
        protected VerifiedStudentsForFinalEnrolmentQuery $verifiedStudentsQuery,
        protected StudentBankPaymentMatcher $paymentMatcher,
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
        protected BulkFinaliseEnrolmentAuditLogger $auditLogger,
    ) {}

    /**
     * @return array{start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    public function resolveDateRange(?string $startDateInput = null, ?string $endDateInput = null): array
    {
        $timezone = (string) config('app.timezone');
        $defaultStartDate = (string) config('custom.bank-statements.plan_anchor_start');
        $startDate = (string) ($startDateInput ?: $defaultStartDate);
        $endDate = (string) ($endDateInput ?: Carbon::now($timezone)->toDateString());

        return [
            'start_date' => CarbonImmutable::parse($startDate, $timezone)->startOfDay(),
            'end_date' => CarbonImmutable::parse($endDate, $timezone)->endOfDay(),
        ];
    }

    public function run(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        bool $dryRun = false,
        ?string $runId = null,
        ?callable $onProgress = null,
        ?int $initiatedByUserId = null,
        array $studentApplicationIds = [],
        bool $forceFinalise = false,
    ): BulkFinaliseEnrolmentsResult {
        $studentApplications = $this->loadVerifiedStudentApplications($studentApplicationIds);
        $step = $this->resolveEnrolledWorkflowStep();
        $successfulFinalised = 0;
        $failedFinalisations = 0;
        $total = $studentApplications->count();

        /** @var array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}> $failures */
        $failures = [];
        /** @var array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}> $successes */
        $successes = [];

        if ($runId !== null) {
            $this->writeRunProgress($runId, [
                'status' => 'running',
                'processed' => 0,
                'total' => $total,
                'successful' => 0,
                'failed' => 0,
                'message' => null,
            ]);
        }

        $studentNumbers = $studentApplications
            ->map(fn (StudentApplication $application): string => (string) ($application->student?->student_number ?? ''))
            ->filter(fn (string $number): bool => $number !== '')
            ->unique()
            ->values()
            ->all();

        $paymentMap = $this->paymentMatcher->matchStudentNumbersInRange(
            $studentNumbers,
            $startDate,
            $endDate,
        );

        $processed = 0;
        $aborted = false;
        $abortMessage = null;

        foreach ($studentApplications as $studentApplication) {
            try {
                $result = $this->processStudentApplication(
                    $studentApplication,
                    $startDate,
                    $endDate,
                    $step,
                    $dryRun,
                    $paymentMap,
                    $forceFinalise,
                    $runId,
                    $initiatedByUserId,
                );
            } catch (StudentEnrolmentResolutionException $exception) {
                if ($this->shouldContinueAfterResolutionException($exception)) {
                    $failedFinalisations++;
                    $failures[] = $this->buildFailureRow(
                        $studentApplication,
                        $startDate,
                        $endDate,
                        'missing_calendar_type',
                    );
                    $processed++;

                    if ($runId !== null) {
                        $this->writeRunProgress($runId, [
                            'status' => 'running',
                            'processed' => $processed,
                            'total' => $total,
                            'successful' => $successfulFinalised,
                            'failed' => $failedFinalisations,
                            'message' => null,
                        ]);
                    }

                    if ($onProgress !== null) {
                        $onProgress();
                    }

                    continue;
                }

                logger()->error('Bulk finalise enrolments failed for student program.', [
                    'student_application_id' => (int) $studentApplication->id,
                    'message' => $exception->getMessage(),
                ]);

                $aborted = true;
                $abortMessage = $exception->getMessage();
                $processed++;

                if ($runId !== null) {
                    $this->writeRunProgress($runId, [
                        'status' => 'failed',
                        'processed' => $processed,
                        'total' => $total,
                        'successful' => $successfulFinalised,
                        'failed' => $failedFinalisations,
                        'message' => $abortMessage,
                    ]);
                }

                break;
            }

            if ($result['successful']) {
                $successfulFinalised++;
                if ($result['success'] !== null) {
                    $successes[] = $result['success'];
                }
            } else {
                $failedFinalisations++;
                if ($result['failure'] !== null) {
                    $failures[] = $result['failure'];
                }
            }

            $processed++;

            if ($runId !== null) {
                $this->writeRunProgress($runId, [
                    'status' => 'running',
                    'processed' => $processed,
                    'total' => $total,
                    'successful' => $successfulFinalised,
                    'failed' => $failedFinalisations,
                    'message' => null,
                ]);
            }

            if ($onProgress !== null) {
                $onProgress();
            }
        }

        $reportPath = null;

        if (! $aborted) {
            $reportPath = $dryRun
                ? $this->writeDryRunReportXlsx($successes, $failures)
                : $this->writeFailureReportXlsx($failures);

            $this->dispatchSummaryEmails(
                successfulFinalised: $successfulFinalised,
                failedFinalisations: $failedFinalisations,
                startDate: $startDate,
                endDate: $endDate,
                reportPath: $reportPath,
                isDryRun: $dryRun,
            );

            if ($runId !== null) {
                $this->writeRunProgress($runId, [
                    'status' => 'completed',
                    'processed' => $processed,
                    'total' => $total,
                    'successful' => $successfulFinalised,
                    'failed' => $failedFinalisations,
                    'message' => null,
                ]);

                if (! $dryRun) {
                    $this->auditLogger->log(
                        runId: $runId,
                        event: BulkFinaliseEnrolmentAuditEventEnum::RunCompleted,
                        userId: $initiatedByUserId,
                        forceFinalise: $forceFinalise,
                        metadata: [
                            'successful' => $successfulFinalised,
                            'failed' => $failedFinalisations,
                            'total' => $total,
                            'student_application_ids' => $studentApplicationIds,
                        ],
                    );
                }
            }
        }

        if ($aborted && $runId !== null && ! $dryRun) {
            $this->auditLogger->log(
                runId: $runId,
                event: BulkFinaliseEnrolmentAuditEventEnum::RunFailed,
                userId: $initiatedByUserId,
                forceFinalise: $forceFinalise,
                reason: $abortMessage,
                metadata: [
                    'successful' => $successfulFinalised,
                    'failed' => $failedFinalisations,
                    'total' => $total,
                    'student_application_ids' => $studentApplicationIds,
                ],
            );
        }

        if ($runId !== null) {
            $this->releaseActiveRun();
        }

        app(VerifiedStudentsForFinalEnrolmentService::class)->forgetSummaryCache();

        return new BulkFinaliseEnrolmentsResult(
            successfulFinalised: $successfulFinalised,
            failedFinalisations: $failedFinalisations,
            successes: $successes,
            failures: $failures,
            reportPath: $reportPath,
            startDate: $startDate,
            endDate: $endDate,
            dryRun: $dryRun,
            aborted: $aborted,
            abortMessage: $abortMessage,
        );
    }

    public function acquireActiveRun(string $runId): bool
    {
        return Cache::add(self::ACTIVE_RUN_LOCK_KEY, $runId, $this->runCacheTtlSeconds());
    }

    public function releaseActiveRun(): void
    {
        Cache::forget(self::ACTIVE_RUN_LOCK_KEY);
    }

    public function isRunActive(): bool
    {
        return Cache::has(self::ACTIVE_RUN_LOCK_KEY);
    }

    public function activeRunId(): ?string
    {
        $runId = Cache::get(self::ACTIVE_RUN_LOCK_KEY);

        return is_string($runId) ? $runId : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function writeRunProgress(string $runId, array $payload): void
    {
        Cache::put(
            self::RUN_CACHE_PREFIX.$runId,
            $payload,
            $this->runCacheTtlSeconds(),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRunProgress(string $runId): ?array
    {
        /** @var array<string, mixed>|null $progress */
        $progress = Cache::get(self::RUN_CACHE_PREFIX.$runId);

        return $progress;
    }

    public function markRunFailed(string $runId, string $message, ?int $initiatedByUserId = null, bool $forceFinalise = false): void
    {
        $progress = $this->getRunProgress($runId) ?? [
            'processed' => 0,
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        $this->writeRunProgress($runId, [
            'status' => 'failed',
            'processed' => (int) ($progress['processed'] ?? 0),
            'total' => (int) ($progress['total'] ?? 0),
            'successful' => (int) ($progress['successful'] ?? 0),
            'failed' => (int) ($progress['failed'] ?? 0),
            'message' => $message,
        ]);

        $this->auditLogger->log(
            runId: $runId,
            event: BulkFinaliseEnrolmentAuditEventEnum::RunFailed,
            userId: $initiatedByUserId,
            forceFinalise: $forceFinalise,
            reason: $message,
            metadata: [
                'processed' => (int) ($progress['processed'] ?? 0),
                'total' => (int) ($progress['total'] ?? 0),
                'successful' => (int) ($progress['successful'] ?? 0),
                'failed' => (int) ($progress['failed'] ?? 0),
            ],
        );

        $this->releaseActiveRun();
    }

    /**
     * @param  list<int>  $studentApplicationIds
     * @return Collection<int, StudentApplication>
     */
    public function loadVerifiedStudentApplications(array $studentApplicationIds = []): Collection
    {
        $query = $this->verifiedStudentsQuery->withRelations();

        if ($studentApplicationIds !== []) {
            $query->whereIn('student_applications.id', $studentApplicationIds);
        }

        return $query->get();
    }

    private function resolveEnrolledWorkflowStep(): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ENROLLED->slug())
            ->first();
    }

    /**
     * @param  array<string, bool>  $paymentMap
     * @return array{successful: bool, success: array<string, mixed>|null, failure: array<string, mixed>|null}
     *
     * @throws StudentEnrolmentResolutionException
     */
    private function processStudentApplication(
        StudentApplication $studentApplication,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?WorkflowStep $step,
        bool $dryRun,
        array $paymentMap = [],
        bool $forceFinalise = false,
        ?string $runId = null,
        ?int $initiatedByUserId = null,
    ): array {
        $student = $studentApplication->student;
        $studentNumber = $student?->student_number;

        if ($studentNumber === null || $studentNumber === '') {
            $this->logStudentSkipped(
                $runId,
                $initiatedByUserId,
                $studentApplication,
                'missing_student_number',
                $forceFinalise,
                'missing_student_number',
                $dryRun,
            );

            return [
                'successful' => false,
                'success' => null,
                'failure' => $this->buildFailureRow($studentApplication, $startDate, $endDate, 'missing_student_number'),
            ];
        }

        $hasPayment = $paymentMap !== []
            ? ($paymentMap[$studentNumber] ?? false)
            : $this->paymentMatcher->hasPaymentInRange($studentNumber, $startDate, $endDate);

        $paymentEligibility = $hasPayment ? 'eligible' : 'no_payment';

        if (! $hasPayment && ! $forceFinalise) {
            $this->logStudentSkipped(
                $runId,
                $initiatedByUserId,
                $studentApplication,
                $paymentEligibility,
                $forceFinalise,
                'no_matching_payment',
                $dryRun,
            );

            return [
                'successful' => false,
                'success' => null,
                'failure' => $this->buildFailureRow($studentApplication, $startDate, $endDate, 'no_matching_payment'),
            ];
        }

        $enrolmentAttributes = $this->resolveStudentEnrolmentAttributes->resolve(
            (int) $studentApplication->student_id,
            (int) $studentApplication->id,
        );

        if (! $dryRun) {
            $this->finaliseClassList($studentApplication);
            $this->updateDepartmentApplicationStep($studentApplication, $step);
            $this->upsertStudentEnrolment($studentApplication, $enrolmentAttributes);

            $this->logStudentFinalised(
                $runId,
                $initiatedByUserId,
                $studentApplication,
                $paymentEligibility,
                $forceFinalise,
            );
        }

        return [
            'successful' => true,
            'success' => $this->buildSummaryRow($studentApplication, $startDate, $endDate, 'would_finalise'),
            'failure' => null,
        ];
    }

    private function logStudentFinalised(
        ?string $runId,
        ?int $initiatedByUserId,
        StudentApplication $studentApplication,
        string $paymentEligibility,
        bool $forceFinalise,
    ): void {
        if ($runId === null) {
            return;
        }

        $this->auditLogger->log(
            runId: $runId,
            event: BulkFinaliseEnrolmentAuditEventEnum::StudentFinalised,
            userId: $initiatedByUserId,
            studentApplication: $studentApplication,
            paymentEligibility: $paymentEligibility,
            forceFinalise: $forceFinalise,
        );
    }

    private function logStudentSkipped(
        ?string $runId,
        ?int $initiatedByUserId,
        StudentApplication $studentApplication,
        string $paymentEligibility,
        bool $forceFinalise,
        string $reason,
        bool $dryRun,
    ): void {
        if ($runId === null || $dryRun) {
            return;
        }

        $this->auditLogger->log(
            runId: $runId,
            event: BulkFinaliseEnrolmentAuditEventEnum::StudentSkipped,
            userId: $initiatedByUserId,
            studentApplication: $studentApplication,
            paymentEligibility: $paymentEligibility,
            forceFinalise: $forceFinalise,
            reason: $reason,
        );
    }

    private function finaliseClassList(StudentApplication $studentApplication): void
    {
        $classList = ClassList::query()->whereKey($studentApplication->classListId)->first();

        if ($classList === null) {
            return;
        }

        $classList->update(['type' => ClassListTypeEnum::FINAL->value]);
    }

    private function updateDepartmentApplicationStep(StudentApplication $studentApplication, ?WorkflowStep $step): void
    {
        $departmentStep = null;
        if ($step !== null) {
            $departmentStep = DepartmentApplicationStep::query()
                ->where('institution_department_id', $studentApplication->institution_department_id)
                ->where('workflow_step_id', $step->id)
                ->first();
        }

        $studentApplication->update([
            'department_application_step_id' => $departmentStep?->id,
        ]);
    }

    /**
     * @param  array{academic_year_option_id:int, academic_calendar_id:int, student_enrolment_status_id:int}  $enrolmentAttributes
     */
    private function upsertStudentEnrolment(StudentApplication $studentApplication, array $enrolmentAttributes): void
    {
        StudentEnrolment::query()->updateOrCreate(
            [
                'student_id' => $studentApplication->student_id,
                'student_application_id' => $studentApplication->id,
                'institution_department_id' => $studentApplication->institution_department_id,
                'department_level_id' => $studentApplication->department_level_id,
                'department_course_id' => $studentApplication->department_course_id,
                'academic_year_option_id' => $enrolmentAttributes['academic_year_option_id'],
                'academic_calendar_id' => $enrolmentAttributes['academic_calendar_id'],
                'mode_of_study_id' => $studentApplication->mode_of_study_id,
            ],
            [
                'student_application_id' => $studentApplication->id,
                'student_enrolment_status_id' => $enrolmentAttributes['student_enrolment_status_id'],
                'mode_of_study_id' => $studentApplication->mode_of_study_id,
            ],
        );
    }

    /**
     * @return array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}
     */
    private function buildFailureRow(
        StudentApplication $studentApplication,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $reason,
    ): array {
        return $this->buildSummaryRow($studentApplication, $startDate, $endDate, $reason);
    }

    /**
     * @return array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}
     */
    private function buildSummaryRow(
        StudentApplication $studentApplication,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $reason,
    ): array {
        $student = $studentApplication->student;

        return [
            'student_application_id' => (int) $studentApplication->id,
            'student_id' => $studentApplication->student_id,
            'student_number' => $student?->student_number,
            'student_id_number' => $student?->id_number,
            'user_full_name' => $student?->user?->full_name,
            'class_list_id' => isset($studentApplication->classListId) ? (int) $studentApplication->classListId : null,
            'reason' => $reason,
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
            'department' => $studentApplication->institutionDepartment?->department?->name,
            'course' => $studentApplication->departmentCourse?->course?->name,
            'level' => $studentApplication->departmentLevel?->level?->name,
        ];
    }

    private function shouldContinueAfterResolutionException(StudentEnrolmentResolutionException $exception): bool
    {
        return str_contains(
            strtolower($exception->getMessage()),
            'calendar type is missing',
        );
    }

    private function dispatchSummaryEmails(
        int $successfulFinalised,
        int $failedFinalisations,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $reportPath,
        bool $isDryRun = false,
    ): void {
        if (! $isDryRun && ! app()->environment('production')) {
            logger()->info('Bulk finalise enrolments: email skipped (non-production environment).');

            return;
        }

        $recipientEmails = User::query()
            ->role(RoleEnum::SUPER_USER->name())
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($recipientEmails) === 0) {
            logger()->warning('Bulk finalise enrolments: no SUPER_USER recipients found.');

            return;
        }

        foreach ($recipientEmails as $email) {
            Mail::to($email)->send(new BulkFinaliseEnrolmentsReportMail(
                successfulFinalised: $successfulFinalised,
                failedFinalisations: $failedFinalisations,
                startDate: $startDate->toDateTimeString(),
                endDate: $endDate->toDateTimeString(),
                reportPath: $reportPath,
                isDryRun: $isDryRun,
            ));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $successes
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function writeDryRunReportXlsx(array $successes, array $failures): ?string
    {
        $timestamp = now()->format('Ymd-His');
        $relativePath = "reports/enrolments/bulk-finalise-dry-run-{$timestamp}.xlsx";
        $absolutePath = Storage::disk('local')->path($relativePath);
        $directoryPath = dirname($absolutePath);

        if (! is_dir($directoryPath)) {
            if (! mkdir($directoryPath, 0755, true) && ! is_dir($directoryPath)) {
                logger()->error('Bulk finalise enrolments: unable to create XLSX report directory.', [
                    'directory' => $directoryPath,
                ]);

                return null;
            }
        }

        Excel::store(new BulkFinaliseDryRunExport($successes, $failures), $relativePath, 'local');

        return $relativePath;
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function writeFailureReportXlsx(array $failures): ?string
    {
        $timestamp = now()->format('Ymd-His');
        $relativePath = "reports/enrolments/bulk-finalise-failures-{$timestamp}.xlsx";
        $absolutePath = Storage::disk('local')->path($relativePath);
        $directoryPath = dirname($absolutePath);

        if (! is_dir($directoryPath)) {
            if (! mkdir($directoryPath, 0755, true) && ! is_dir($directoryPath)) {
                logger()->error('Bulk finalise enrolments: unable to create XLSX report directory.', [
                    'directory' => $directoryPath,
                ]);

                return null;
            }
        }

        Excel::store(new BulkFinaliseFailuresExport($failures), $relativePath, 'local');

        return $relativePath;
    }
}
