<?php

namespace App\Console\Commands\Enrolments;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BulkFinaliseEnrolmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrolments:bulk-finalise-enrolments-command
                            {--start-date= : Inclusive payment start date (Y-m-d). Defaults to custom.bank-statements.plan_anchor_start}
                            {--end-date= : Inclusive payment end date (Y-m-d). Defaults to now()}';

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
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->resolveDateRange();
        $studentPrograms = $this->loadVerifiedStudentPrograms();
        $step = $this->resolveEnrolledWorkflowStep();
        $successfulFinalised = 0;
        $failedFinalisations = 0;

        $this->output->progressStart($studentPrograms->count());

        /** @var array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int, reason:string, start_date:string, end_date:string}> $failures */
        $failures = [];

        foreach ($studentPrograms as $studentProgram) {
            try {
                $result = $this->processStudentProgram(
                    $studentProgram,
                    $startDate,
                    $endDate,
                    $step,
                    $resolveStudentEnrolmentAttributes,
                );
            } catch (StudentEnrolmentResolutionException $exception) {
                $this->output->progressFinish();
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if ($result['successful']) {
                $successfulFinalised++;
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

        $reportPath = $this->writeFailureReportCsv($failures);
        if ($reportPath !== null) {
            $this->info("Failure report saved: {$reportPath}");
            logger()->info('Bulk finalise enrolments failure report saved.', [
                'path' => $reportPath,
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
     * @return Collection<int, StudentProgram>
     */
    private function loadVerifiedStudentPrograms(): Collection
    {
        return StudentProgram::query()
            ->join('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')
            ->select('student_programs.*', 'class_lists.id as classListId')
            ->with(['student.user'])
            ->where('class_lists.type', ClassListTypeEnum::VERIFIED->value)
            ->get();
    }

    private function resolveEnrolledWorkflowStep(): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ENROLLED->slug())
            ->first();
    }

    /**
     * @return array{successful: bool, failure: array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int, reason:string, start_date:string, end_date:string}|null}
     *
     * @throws StudentEnrolmentResolutionException
     */
    private function processStudentProgram(
        StudentProgram $studentProgram,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?WorkflowStep $step,
        ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
    ): array {
        $student = $studentProgram->student;
        $studentNumber = $student?->student_number;

        if ($studentNumber === null || $studentNumber === '') {
            return [
                'successful' => false,
                'failure' => $this->buildFailureRow($studentProgram, $startDate, $endDate, 'missing_student_number'),
            ];
        }

        if (! $this->studentHasPaymentInRange($studentNumber, $startDate, $endDate)) {
            return [
                'successful' => false,
                'failure' => $this->buildFailureRow($studentProgram, $startDate, $endDate, 'no_matching_payment'),
            ];
        }

        $enrolmentAttributes = $resolveStudentEnrolmentAttributes->resolve(
            (int) $studentProgram->student_id,
            (int) $studentProgram->id,
        );

        $this->finaliseClassList($studentProgram);
        $this->updateDepartmentApplicationStep($studentProgram, $step);
        $this->upsertStudentEnrolment($studentProgram, $enrolmentAttributes);

        return [
            'successful' => true,
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

    private function finaliseClassList(StudentProgram $studentProgram): void
    {
        ClassList::query()
            ->whereKey($studentProgram->classListId)
            ->update(['type' => ClassListTypeEnum::FINAL->value]);
    }

    private function updateDepartmentApplicationStep(StudentProgram $studentProgram, ?WorkflowStep $step): void
    {
        $departmentStep = null;
        if ($step !== null) {
            $departmentStep = DepartmentApplicationStep::query()
                ->where('institution_department_id', $studentProgram->institution_department_id)
                ->where('workflow_step_id', $step->id)
                ->first();
        }

        $studentProgram->update([
            'department_application_step_id' => $departmentStep?->id,
        ]);
    }

    /**
     * @param  array{academic_year_option_id:int, academic_calendar_id:int, student_enrolment_status_id:int}  $enrolmentAttributes
     */
    private function upsertStudentEnrolment(StudentProgram $studentProgram, array $enrolmentAttributes): void
    {
        StudentEnrolment::query()->updateOrCreate(
            [
                'student_id' => $studentProgram->student_id,
                'student_program_id' => $studentProgram->id,
                'institution_department_id' => $studentProgram->institution_department_id,
                'department_level_id' => $studentProgram->department_level_id,
                'department_course_id' => $studentProgram->department_course_id,
                'academic_year_option_id' => $enrolmentAttributes['academic_year_option_id'],
                'academic_calendar_id' => $enrolmentAttributes['academic_calendar_id'],
            ],
            [
                'student_program_id' => $studentProgram->id,
                'student_enrolment_status_id' => $enrolmentAttributes['student_enrolment_status_id'],
            ],
        );
    }

    /**
     * @return array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int, reason:string, start_date:string, end_date:string}
     */
    private function buildFailureRow(
        StudentProgram $studentProgram,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $reason,
    ): array {
        $student = $studentProgram->student;

        return [
            'student_program_id' => (int) $studentProgram->id,
            'student_id' => $studentProgram->student_id,
            'student_number' => $student?->student_number,
            'student_id_number' => $student?->id_number,
            'user_full_name' => $student?->user?->full_name,
            'class_list_id' => (int) $studentProgram->classListId,
            'reason' => $reason,
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
        ];
    }

    private function dispatchSummaryEmails(
        int $successfulFinalised,
        int $failedFinalisations,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $reportPath,
    ): void {
        if (! app()->environment('production')) {
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
            ));
        }
    }

    /**
     * @param  array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int, reason:string, start_date:string, end_date:string}>  $failures
     */
    private function writeFailureReportCsv(array $failures): ?string
    {
        $timestamp = now()->format('Ymd-His');
        $relativePath = "reports/enrolments/bulk-finalise-failures-{$timestamp}.csv";

        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            logger()->error('Bulk finalise enrolments: unable to open temp stream for CSV.');

            return null;
        }

        fputcsv($handle, [
            'student_program_id',
            'student_id',
            'user_full_name',
            'student_id_number',
            'student_number',
            'class_list_id',
            'reason',
            'start_date',
            'end_date',
        ]);

        foreach ($failures as $row) {
            fputcsv($handle, [
                $row['student_program_id'],
                $row['student_id'],
                $row['user_full_name'],
                $row['student_id_number'],
                $row['student_number'],
                $row['class_list_id'],
                $row['reason'],
                $row['start_date'],
                $row['end_date'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if (! is_string($csv)) {
            logger()->error('Bulk finalise enrolments: unable to read CSV contents.');

            return null;
        }

        Storage::disk('local')->put($relativePath, $csv);

        return $relativePath;
    }
}
