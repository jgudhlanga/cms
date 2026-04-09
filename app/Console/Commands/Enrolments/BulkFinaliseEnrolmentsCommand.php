<?php

namespace App\Console\Commands\Enrolments;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Mail\Enrolments\BulkFinaliseEnrolmentsReportMail;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
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
    public function handle(): int
    {
        $timezone = (string) config('app.timezone');
        $defaultStartDate = (string) config('custom.bank-statements.plan_anchor_start');
        $startDateInput = (string) ($this->option('start-date') ?: $defaultStartDate);
        $endDateInput = (string) ($this->option('end-date') ?: Carbon::now($timezone)->toDateString());
        $startDate = CarbonImmutable::parse($startDateInput, $timezone)->startOfDay();
        $endDate = CarbonImmutable::parse($endDateInput, $timezone)->endOfDay();

        $studentPrograms = StudentProgram::query()
            ->join('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')
            ->select('student_programs.*', 'class_lists.id as classListId')
            ->with(['student.user'])
            ->where('class_lists.type', ClassListTypeEnum::VERIFIED->value)
            ->get();

        $step = WorkflowStep::where('slug', WorkflowStepEnum::ENROLLED->slug())->first();
        $successfulFinalised = 0;
        $failedFinalisations = 0;

        $this->output->progressStart($studentPrograms->count());

        /** @var array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int, reason:string, start_date:string, end_date:string}> $failures */
        $failures = [];

        foreach ($studentPrograms as $studentProgram) {
            $student = $studentProgram->student;
            $studentNumber = $student?->student_number;

            if ($studentNumber) {
                $escapedStudentNumber = addcslashes($studentNumber, '\%_');
                $studentNumberPattern = "%{$escapedStudentNumber}%";

                $hasPaid = ZBBankStatement::query()
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

                if ($hasPaid) {
                    ClassList::query()
                        ->whereKey($studentProgram->classListId)
                        ->update(['type' => ClassListTypeEnum::FINAL->value]);

                    $departmentStep = null;
                    if ($step !== null) {
                        $departmentStep = DepartmentApplicationStep::where('institution_department_id', $studentProgram->institution_department_id)
                            ->where('workflow_step_id', $step->id)
                            ->first();
                    }

                    $studentProgram->update([
                        'department_application_step_id' => $departmentStep?->id,
                    ]);
                    $successfulFinalised++;
                } else {
                    $failedFinalisations++;
                    $failures[] = [
                        'student_program_id' => (int) $studentProgram->id,
                        'student_id' => $studentProgram->student_id,
                        'student_number' => $studentNumber,
                        'student_id_number' => $student?->id_number,
                        'user_full_name' => $student?->user?->full_name,
                        'class_list_id' => (int) $studentProgram->classListId,
                        'reason' => 'no_matching_payment',
                        'start_date' => $startDate->toDateTimeString(),
                        'end_date' => $endDate->toDateTimeString(),
                    ];
                }
            } else {
                $failedFinalisations++;
                $failures[] = [
                    'student_program_id' => (int) $studentProgram->id,
                    'student_id' => $studentProgram->student_id,
                    'student_number' => null,
                    'student_id_number' => $student?->id_number,
                    'user_full_name' => $student?->user?->full_name,
                    'class_list_id' => (int) $studentProgram->classListId,
                    'reason' => 'missing_student_number',
                    'start_date' => $startDate->toDateTimeString(),
                    'end_date' => $endDate->toDateTimeString(),
                ];
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

        if (app()->environment('production')) {
            $recipientEmails = User::query()
                ->role(RoleEnum::SUPER_USER->name())
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (count($recipientEmails) > 0) {
                foreach ($recipientEmails as $email) {
                    Mail::to($email)->send(new BulkFinaliseEnrolmentsReportMail(
                        successfulFinalised: $successfulFinalised,
                        failedFinalisations: $failedFinalisations,
                        startDate: $startDate->toDateTimeString(),
                        endDate: $endDate->toDateTimeString(),
                        reportPath: $reportPath,
                    ));
                }
            } else {
                logger()->warning('Bulk finalise enrolments: no SUPER_USER recipients found.');
            }
        } else {
            logger()->info('Bulk finalise enrolments: email skipped (non-production environment).');
        }

        return self::SUCCESS;
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
