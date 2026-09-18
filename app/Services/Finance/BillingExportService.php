<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\StudentBillingStatusEnum;
use App\Http\Requests\Finance\ExportForBillingRequest;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Finance\StudentBillingBatch;
use App\Models\Finance\StudentBillingRecord;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Queries\Finance\BillingExportQuery;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Exports\CsvExportWriter;
use App\Support\Exports\StudentExportRowMapper;
use App\Support\Finance\BillingExportFilters;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BillingExportService
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'student_number',
        'national_id_number',
        'department',
        'level',
        'course',
        'phase',
        'billing_period',
        'mode_of_study',
        'residence',
        'phone_number',
    ];

    public function __construct(
        protected BillingExportQuery $query,
        protected CsvExportWriter $csvExportWriter,
        protected StudentExportRowMapper $studentExportRowMapper,
    ) {}

    public function export(ExportForBillingRequest $request): string
    {
        $filters = BillingExportFilters::fromExportRequest($request);
        $fallbackTenantId = (int) (Auth::user()?->tenant_id ?? 0);
        $now = now();
        $exportedBy = Auth::id();
        $reference = (string) Str::uuid();
        $relativePath = 'reports/finance/billing-export-'.$reference.'.csv';

        /** @var array<int, array<string, mixed>> $recordsToInsert */
        $recordsToInsert = [];

        return DB::transaction(function () use (
            $filters,
            $fallbackTenantId,
            $now,
            $exportedBy,
            $reference,
            $relativePath,
            &$recordsToInsert,
        ): string {
            $batch = StudentBillingBatch::query()->create([
                'tenant_id' => $fallbackTenantId,
                'reference' => $reference,
                'filters' => $filters->toArray(),
                'row_count' => 0,
                'status' => StudentBillingStatusEnum::EXPORTED,
                'exported_by' => $exportedBy,
                'exported_at' => $now,
            ]);

            $path = $this->csvExportWriter->write(
                $relativePath,
                self::HEADERS,
                function ($handle) use ($filters, $fallbackTenantId, $now, $batch, &$recordsToInsert): void {
                    $this->query
                        ->baseQuery($filters)
                        ->chunkById(200, function (Collection $confirmations) use ($handle, $fallbackTenantId, $now, $batch, &$recordsToInsert): void {
                            foreach ($confirmations as $confirmation) {
                                /** @var StudentStudyPositionConfirmation $confirmation */
                                fputcsv($handle, $this->mapRow($confirmation));

                                $studentId = $confirmation->student_id;
                                $enrolmentId = $confirmation->student_enrolment_id;
                                $programmeSemesterId = $confirmation->programme_semester_id;

                                if ($studentId === null || $enrolmentId === null || $programmeSemesterId === null) {
                                    continue;
                                }

                                $uniqueKey = $enrolmentId.'-'.$confirmation->academic_calendar_id.'-'.$programmeSemesterId;

                                if (isset($recordsToInsert[$uniqueKey])) {
                                    continue;
                                }

                                $student = $confirmation->enrolment?->student;
                                $tenantId = (int) ($student?->tenant_id ?? $confirmation->tenant_id ?? $fallbackTenantId);

                                if ($tenantId < 1) {
                                    continue;
                                }

                                $recordsToInsert[$uniqueKey] = [
                                    'tenant_id' => $tenantId,
                                    'student_id' => (int) $studentId,
                                    'student_enrolment_id' => (int) $enrolmentId,
                                    'academic_calendar_id' => (int) $confirmation->academic_calendar_id,
                                    'programme_semester_id' => (int) $programmeSemesterId,
                                    'semester_id' => $confirmation->semester_id,
                                    'student_study_position_confirmation_id' => (int) $confirmation->id,
                                    'student_billing_batch_id' => (int) $batch->id,
                                    'student_number' => $student?->student_number,
                                    'status' => StudentBillingStatusEnum::EXPORTED->value,
                                    'exported_at' => $now,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }, column: 'id');
                },
            );

            $absolutePath = Storage::disk('local')->path($path);

            if (is_file($absolutePath)) {
                chmod($absolutePath, 0600);
            }

            if ($recordsToInsert !== []) {
                DB::table((new StudentBillingRecord)->getTable())->insertOrIgnore(array_values($recordsToInsert));
            }

            $batch->update([
                'row_count' => count($recordsToInsert),
            ]);

            return $path;
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function markBilled(array $ids, int $userId): int
    {
        if ($ids === []) {
            return 0;
        }

        $now = now();

        return (int) DB::transaction(function () use ($ids, $userId, $now): int {
            $updated = StudentBillingRecord::query()
                ->whereIn('id', $ids)
                ->where('status', StudentBillingStatusEnum::EXPORTED)
                ->update([
                    'status' => StudentBillingStatusEnum::BILLED->value,
                    'billed_at' => $now,
                    'billed_by' => $userId,
                    'updated_at' => $now,
                ]);

            $this->syncBatchStatusesForRecordIds($ids);

            return $updated;
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function markFailed(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return (int) DB::transaction(function () use ($ids): int {
            $records = StudentBillingRecord::query()
                ->whereIn('id', $ids)
                ->where('status', StudentBillingStatusEnum::EXPORTED)
                ->get(['id', 'student_billing_batch_id']);

            $deleted = StudentBillingRecord::query()
                ->whereIn('id', $records->pluck('id'))
                ->delete();

            $this->syncBatchStatusesForBatchIds(
                $records->pluck('student_billing_batch_id')->unique()->filter()->map(fn ($id): int => (int) $id)->all(),
            );

            return $deleted;
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function unbill(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return (int) DB::transaction(function () use ($ids): int {
            $records = StudentBillingRecord::query()
                ->whereIn('id', $ids)
                ->where('status', StudentBillingStatusEnum::BILLED)
                ->get(['id', 'student_billing_batch_id']);

            $deleted = StudentBillingRecord::query()
                ->whereIn('id', $records->pluck('id'))
                ->delete();

            $this->syncBatchStatusesForBatchIds(
                $records->pluck('student_billing_batch_id')->unique()->filter()->map(fn ($id): int => (int) $id)->all(),
            );

            return $deleted;
        });
    }

    /**
     * @return list<string|null>
     */
    public function mapRow(StudentStudyPositionConfirmation $confirmation): array
    {
        $enrolment = $confirmation->enrolment;
        $student = $enrolment?->student;
        $studentApplication = $enrolment?->studentApplication;
        $levelName = $enrolment?->departmentLevel?->level?->name;
        $calendar = $confirmation->academicCalendar;

        return [
            $this->csvCell($student?->student_number),
            $this->csvCell($this->studentExportRowMapper->resolveNationalId($student)),
            $this->csvCell($enrolment?->institutionDepartment?->department?->name),
            $this->csvCell($levelName),
            $this->csvCell($enrolment?->departmentCourse?->course?->name),
            $this->csvCell(
                $confirmation->programmeSemester !== null
                    ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $confirmation->programmeSemester->name)
                    : null,
            ),
            $this->csvCell($this->billingPeriodLabel($calendar)),
            $this->csvCell($studentApplication?->modeOfStudy?->name ?? $enrolment?->modeOfStudy?->name),
            $student?->activeHostelAllocation !== null ? 'Resident' : 'Non-Resident',
            $this->csvCell($this->studentExportRowMapper->resolvePhone($student)),
        ];
    }

    private function billingPeriodLabel(?AcademicCalendar $calendar): ?string
    {
        if (! $calendar instanceof AcademicCalendar) {
            return null;
        }

        return trim((string) $calendar->calendar_year).' · '.AcademicCalendarPeriodResolver::displayPeriodLabel($calendar);
    }

    private function csvCell(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @param  list<int>  $recordIds
     */
    private function syncBatchStatusesForRecordIds(array $recordIds): void
    {
        $batchIds = StudentBillingRecord::query()
            ->whereIn('id', $recordIds)
            ->pluck('student_billing_batch_id')
            ->unique()
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->syncBatchStatusesForBatchIds($batchIds);
    }

    /**
     * @param  list<int>  $batchIds
     */
    private function syncBatchStatusesForBatchIds(array $batchIds): void
    {
        if ($batchIds === []) {
            return;
        }

        $now = now();

        foreach (array_unique($batchIds) as $batchId) {
            $batch = StudentBillingBatch::query()->find($batchId);

            if (! $batch instanceof StudentBillingBatch) {
                continue;
            }

            $remaining = StudentBillingRecord::query()
                ->where('student_billing_batch_id', $batchId)
                ->get(['status']);

            if ($remaining->isEmpty()) {
                $batch->update([
                    'status' => StudentBillingStatusEnum::FAILED,
                    'note' => $batch->note,
                ]);

                continue;
            }

            $allBilled = $remaining->every(
                fn (StudentBillingRecord $record): bool => $record->status === StudentBillingStatusEnum::BILLED,
            );

            if ($allBilled) {
                $batch->update([
                    'status' => StudentBillingStatusEnum::BILLED,
                    'billed_at' => $batch->billed_at ?? $now,
                    'billed_by' => $batch->billed_by ?? Auth::id(),
                ]);

                continue;
            }

            $batch->update([
                'status' => StudentBillingStatusEnum::EXPORTED,
                'billed_at' => null,
                'billed_by' => null,
            ]);
        }
    }
}
