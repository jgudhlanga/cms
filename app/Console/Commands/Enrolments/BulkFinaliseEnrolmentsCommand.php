<?php

namespace App\Console\Commands\Enrolments;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Exports\Enrolments\BulkFinaliseDryRunExport;
use App\Exports\Enrolments\BulkFinaliseFailuresExport;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BulkFinaliseEnrolmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrolments:bulk-finalise-enrolments-command
                            {--start-date= : Inclusive payment start date (Y-m-d). Defaults to custom.bank-statements.plan_anchor_start}
                            {--end-date= : Inclusive payment end date (Y-m-d). Defaults to now()}
                            {--dry-run : Preview results without writing changes; still generates a report and emails it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk finalise enrolments';

    /**
     * Execute the console command.
     */
    public function handle(ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes): int
    {
        $dryRun = (bool) $this->option('dry-run');
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->resolveDateRange();
        $studentApplications = $this->loadVerifiedStudentApplications();
        $step = $this->resolveEnrolledWorkflowStep();
        $successfulFinalised = 0;
        $failedFinalisations = 0;

        $this->output->progressStart($studentApplications->count());

        /** @var array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}> $failures */
        $failures = [];
        /** @var array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}> $successes */
        $successes = [];

        foreach ($studentApplications as $studentApplication) {
            try {
                $result = $this->processStudentApplication(
                    $studentApplication,
                    $startDate,
                    $endDate,
                    $step,
                    $resolveStudentEnrolmentAttributes,
                    $dryRun,
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

                    $this->output->progressAdvance();

                    continue;
                }

                logger()->error('Bulk finalise enrolments failed for student program.', [
                    'student_application_id' => (int) $studentApplication->id,
                    'message' => $exception->getMessage(),
                ]);
                $this->output->progressFinish();
                $this->error($exception->getMessage());

                return self::FAILURE;
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

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->newLine();
        $this->info("Successfully finalised: {$successfulFinalised}");
        $this->warn("Failed finalisations: {$failedFinalisations}");

        $reportPath = $dryRun
            ? $this->writeDryRunReportXlsx($successes, $failures)
            : $this->writeFailureReportXlsx($failures);
        if ($reportPath !== null) {
            $this->info("Report saved: {$reportPath}");
            logger()->info('Bulk finalise enrolments report saved.', [
                'path' => $reportPath,
                'dry_run' => $dryRun,
                'successful_finalised' => $successfulFinalised,
                'failed_finalisations' => $failedFinalisations,
            ]);
        }

        $this->dispatchSummaryEmails(
            successfulFinalised: $successfulFinalised,
            failedFinalisations: $failedFinalisations,
            startDate: $startDate,
            endDate: $endDate,
            reportPath: $reportPath,
            isDryRun: $dryRun,
        );

        return self::SUCCESS;
    }

    /**
     * @return array{start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    private function resolveDateRange(): array
    {
        $timezone = (string) config('app.timezone');
        $defaultStartDate = (string) config('custom.bank-statements.plan_anchor_start');
        $startDateInput = (string) ($this->option('start-date') ?: $defaultStartDate);
        $endDateInput = (string) ($this->option('end-date') ?: Carbon::now($timezone)->toDateString());

        return [
            'start_date' => CarbonImmutable::parse($startDateInput, $timezone)->startOfDay(),
            'end_date' => CarbonImmutable::parse($endDateInput, $timezone)->endOfDay(),
        ];
    }

    /**
     * @return Collection<int, StudentApplication>
     */
    private function loadVerifiedStudentApplications(): Collection
    {
        return StudentApplication::query()
            ->join('class_lists', 'class_lists.student_application_id', '=', 'student_applications.id')
            ->select('student_applications.*', 'class_lists.id as classListId')
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentCourse.course',
                'departmentLevel.level',
            ])
            ->where('class_lists.type', ClassListTypeEnum::VERIFIED->value)
            ->whereHas('departmentWorkflowStep.workflowStep', function ($query): void {
                $query->where('name', WorkflowStepEnum::ACCEPTED->name());
            })
            ->get();
    }

    private function resolveEnrolledWorkflowStep(): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ENROLLED->slug())
            ->first();
    }

    /**
     * @return array{successful: bool, success: array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}|null, failure: array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}|null}
     *
     * @throws StudentEnrolmentResolutionException
     */
    private function processStudentApplication(
        StudentApplication $studentApplication,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?WorkflowStep $step,
        ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
        bool $dryRun,
    ): array {
        $student = $studentApplication->student;
        $studentNumber = $student?->student_number;

        if ($studentNumber === null || $studentNumber === '') {
            return [
                'successful' => false,
                'success' => null,
                'failure' => $this->buildFailureRow($studentApplication, $startDate, $endDate, 'missing_student_number'),
            ];
        }

        if (! $this->studentHasPaymentInRange($studentNumber, $startDate, $endDate)) {
            return [
                'successful' => false,
                'success' => null,
                'failure' => $this->buildFailureRow($studentApplication, $startDate, $endDate, 'no_matching_payment'),
            ];
        }

        $enrolmentAttributes = $resolveStudentEnrolmentAttributes->resolve(
            (int) $studentApplication->student_id,
            (int) $studentApplication->id,
        );

        if (! $dryRun) {
            $this->finaliseClassList($studentApplication);
            $this->updateDepartmentApplicationStep($studentApplication, $step);
            $this->upsertStudentEnrolment($studentApplication, $enrolmentAttributes);
        }

        return [
            'successful' => true,
            'success' => $this->buildSummaryRow($studentApplication, $startDate, $endDate, 'would_finalise'),
            'failure' => null,
        ];
    }

    private function studentHasPaymentInRange(string $studentNumber, CarbonImmutable $startDate, CarbonImmutable $endDate): bool
    {
        $escapedStudentNumber = addcslashes($studentNumber, '\%_');
        $studentNumberPattern = "%{$escapedStudentNumber}%";

        return ZBBankStatement::query()
            ->where('debit_credit_flag', 'C')
            ->whereBetween('transaction_date', [
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
            ])
            ->where(function ($statementQuery) use ($studentNumberPattern): void {
                $statementQuery
                    ->where('narration', 'like', $studentNumberPattern)
                    ->orWhere('pipe5_details', 'like', $studentNumberPattern)
                    ->orWhere('pipe10_details', 'like', $studentNumberPattern)
                    ->orWhere('transaction_details', 'like', $studentNumberPattern);
            })
            ->exists();
    }

    private function finaliseClassList(StudentApplication $studentApplication): void
    {
        ClassList::query()
            ->whereKey($studentApplication->classListId)
            ->update(['type' => ClassListTypeEnum::FINAL->value]);
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
     * @param  array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $successes
     * @param  array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $failures
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
     * @param  array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $failures
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
