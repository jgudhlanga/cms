<?php

namespace App\Services\Examinations;

use App\Enums\Examinations\ExaminationImportSourceEnum;
use App\Enums\Examinations\ExaminationImportStatusEnum;
use App\Jobs\Examinations\ImportExaminationResultsJob;
use App\Mail\Examinations\ExaminationImportStartedMail;
use App\Models\Examinations\ExaminationImport;
use App\Models\Examinations\ExaminationResult;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Support\Examinations\ExaminationDumpColumns;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

class ExaminationImportService
{
    /**
     * @return list<string>
     */
    public function notifyRecipients(?User $starter = null): array
    {
        if ($starter instanceof User && filled($starter->email)) {
            return [(string) $starter->email];
        }

        $configured = config('examinations.notify', []);

        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter(array_map('strval', $configured)));
        }

        return User::query()
            ->permission('import:examinations')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    public function startFromUpload(UploadedFile $file, User $user): ExaminationImport
    {
        $this->assertAllowedExtension($file->getClientOriginalExtension());

        $disk = Storage::disk('local');
        $directory = (string) config('examinations.uploads_path', 'examinations/uploads');
        $disk->makeDirectory($directory);

        $storedName = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs($directory, $storedName, 'local');

        if ($storedPath === false) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_store_failed')],
            ]);
        }

        $fullPath = $disk->path($storedPath);
        $headerRowIndex = $this->headerRowIndex($fullPath);
        $this->assertFileHasRequiredHeaders($fullPath, $headerRowIndex);

        $import = ExaminationImport::query()->create([
            'tenant_id' => $user->tenant_id,
            'source' => ExaminationImportSourceEnum::Upload,
            'status' => ExaminationImportStatusEnum::Pending,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'started_by' => $user->id,
        ]);

        $this->dispatchImport($import, $user);

        return $import->fresh() ?? $import;
    }

    public function startFromWatcherPath(string $absolutePath, ?int $tenantId = null): ExaminationImport
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $this->assertAllowedExtension($extension);

        if (! is_file($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_file_missing')],
            ]);
        }

        $headerRowIndex = $this->headerRowIndex($absolutePath);
        $this->assertFileHasRequiredHeaders($absolutePath, $headerRowIndex);

        $disk = Storage::disk('local');
        $processingDir = (string) config('examinations.processing_path', 'examinations/processing');
        $disk->makeDirectory($processingDir);

        $storedName = Str::uuid()->toString().'.'.$extension;
        $storedPath = $processingDir.'/'.$storedName;
        $targetAbsolute = $disk->path($storedPath);

        if (! @rename($absolutePath, $targetAbsolute) && ! @copy($absolutePath, $targetAbsolute)) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_store_failed')],
            ]);
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        $resolvedTenantId = $tenantId
            ?? User::query()->whereNotNull('tenant_id')->value('tenant_id');

        if ($resolvedTenantId === null) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_tenant_missing')],
            ]);
        }

        $import = ExaminationImport::query()->create([
            'tenant_id' => $resolvedTenantId,
            'source' => ExaminationImportSourceEnum::Watcher,
            'status' => ExaminationImportStatusEnum::Pending,
            'original_filename' => basename($absolutePath),
            'stored_path' => $storedPath,
            'started_by' => null,
        ]);

        $this->dispatchImport($import);

        return $import->fresh() ?? $import;
    }

    public function dispatchImport(ExaminationImport $import, ?User $starter = null): void
    {
        $recipients = $this->notifyRecipients($starter ?? $import->starter);

        if ($recipients !== []) {
            Mail::to($recipients)->queue(new ExaminationImportStartedMail($import));
        }

        ImportExaminationResultsJob::dispatch($import->id);
    }

    /**
     * @return array{upserted: int, failed: int, processed: int}
     */
    public function processImport(ExaminationImport $import): array
    {
        $import->forceFill([
            'status' => ExaminationImportStatusEnum::Processing,
            'started_at' => $import->started_at ?? now(),
            'error_message' => null,
        ])->save();

        $absolutePath = Storage::disk('local')->path($import->stored_path);

        if (! is_file($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_file_missing')],
            ]);
        }

        $headerRowIndex = $this->headerRowIndex($absolutePath);
        $this->assertFileHasRequiredHeaders($absolutePath, $headerRowIndex);

        $chunkSize = max(1, (int) config('examinations.chunk_size', 500));
        $studentMap = $this->buildStudentNumberMap((int) $import->tenant_id);

        $reader = SimpleExcelReader::create($absolutePath)
            ->headerOnRow($headerRowIndex);

        $rowsTotal = 0;
        $processed = 0;
        $upserted = 0;
        $failed = 0;
        $buffer = [];

        foreach ($reader->getRows() as $row) {
            $rowsTotal++;

            if (! is_array($row)) {
                $failed++;
                $processed++;

                continue;
            }

            $candidate = ExaminationDumpColumns::cell($row, ExaminationDumpColumns::CANDIDATE_NUMBER);
            $subjectCode = ExaminationDumpColumns::cell($row, ExaminationDumpColumns::SUBJECT_CODE);
            $session = ExaminationDumpColumns::cell($row, ExaminationDumpColumns::SESSION);

            if ($candidate === null || $subjectCode === null || $session === null) {
                $failed++;
                $processed++;

                continue;
            }

            $sessionDate = ExaminationDumpColumns::excelSerialToDate($session);

            $buffer[] = [
                'tenant_id' => $import->tenant_id,
                'examination_import_id' => $import->id,
                'student_id' => $studentMap[$candidate] ?? null,
                'discipline' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::DISCIPLINE),
                'course_code' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::COURSE_CODE),
                'candidate_number' => $candidate,
                'surname' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::SURNAME),
                'first_names' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::FIRST_NAMES),
                'subject_code' => $subjectCode,
                'subject' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::SUBJECT),
                'grade' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::GRADE),
                'session' => $session,
                'session_date' => $sessionDate?->toDateString(),
                'course_comment' => ExaminationDumpColumns::cell($row, ExaminationDumpColumns::COURSE_COMMENT),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($buffer) >= $chunkSize) {
                $upserted += $this->upsertChunk($buffer);
                $processed += count($buffer);
                $buffer = [];

                $import->forceFill([
                    'rows_total' => $rowsTotal,
                    'rows_processed' => $processed,
                    'rows_upserted' => $upserted,
                    'rows_failed' => $failed,
                ])->save();
            }
        }

        if ($buffer !== []) {
            $upserted += $this->upsertChunk($buffer);
            $processed += count($buffer);
        }

        $import->forceFill([
            'status' => ExaminationImportStatusEnum::Completed,
            'rows_total' => $rowsTotal,
            'rows_processed' => $processed,
            'rows_upserted' => $upserted,
            'rows_failed' => $failed,
            'completed_at' => now(),
        ])->save();

        $this->archiveProcessedFile($import);

        return [
            'upserted' => $upserted,
            'failed' => $failed,
            'processed' => $processed,
        ];
    }

    public function markFailed(ExaminationImport $import, Throwable $exception): void
    {
        $import->forceFill([
            'status' => ExaminationImportStatusEnum::Failed,
            'error_message' => Str::limit($exception->getMessage(), 2000),
            'completed_at' => now(),
        ])->save();

        $this->archiveFailedFile($import);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertChunk(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        ExaminationResult::query()->upsert(
            $rows,
            ['tenant_id', 'candidate_number', 'subject_code', 'session'],
            [
                'examination_import_id',
                'student_id',
                'discipline',
                'course_code',
                'surname',
                'first_names',
                'subject',
                'grade',
                'session_date',
                'course_comment',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * @return array<string, int>
     */
    private function buildStudentNumberMap(int $tenantId): array
    {
        return Student::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('student_number')
            ->pluck('id', 'student_number')
            ->all();
    }

    private function assertAllowedExtension(string $extension): void
    {
        $allowed = config('examinations.allowed_extensions', ['xlsx', 'xls', 'csv']);
        $extension = strtolower(ltrim($extension, '.'));

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => [__('examinations.import_invalid_extension', [
                    'extensions' => implode(', ', $allowed),
                ])],
            ]);
        }
    }

    private function assertFileHasRequiredHeaders(string $absolutePath, int $headerRowIndex): void
    {
        $headers = SimpleExcelReader::create($absolutePath)
            ->headerOnRow($headerRowIndex)
            ->getHeaders() ?? [];

        try {
            ExaminationDumpColumns::assertHeadersPresent(array_values($headers));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }
    }

    /**
     * Zero-based index of the header row (blank leading rows are skipped).
     */
    public function headerRowIndex(string $absolutePath): int
    {
        $reader = SimpleExcelReader::create($absolutePath)->noHeaderRow();

        $index = 0;
        foreach ($reader->getRows() as $row) {
            if (! is_array($row)) {
                $index++;

                continue;
            }

            $values = array_map(
                static function ($v) {
                    if ($v === null) {
                        return '';
                    }

                    return trim((string) $v);
                },
                array_values($row),
            );

            $nonEmpty = array_values(array_filter($values, static fn (string $v): bool => $v !== ''));

            if ($nonEmpty === []) {
                $index++;

                continue;
            }

            if (
                in_array(ExaminationDumpColumns::CANDIDATE_NUMBER, $nonEmpty, true)
                || in_array(ExaminationDumpColumns::SUBJECT_CODE, $nonEmpty, true)
            ) {
                return $index;
            }

            $index++;

            if ($index > 20) {
                break;
            }
        }

        return 0;
    }

    private function archiveProcessedFile(ExaminationImport $import): void
    {
        $this->moveStoredFile($import, (string) config('examinations.processed_path', 'examinations/processed'));
    }

    private function archiveFailedFile(ExaminationImport $import): void
    {
        $this->moveStoredFile($import, (string) config('examinations.failed_path', 'examinations/failed'));
    }

    private function moveStoredFile(ExaminationImport $import, string $targetDirectory): void
    {
        $disk = Storage::disk('local');
        $from = $import->stored_path;

        if (! $disk->exists($from)) {
            return;
        }

        $disk->makeDirectory($targetDirectory);
        $to = $targetDirectory.'/'.basename($from);

        if ($from === $to) {
            return;
        }

        if ($disk->exists($to)) {
            $to = $targetDirectory.'/'.Str::uuid()->toString().'_'.basename($from);
        }

        $disk->move($from, $to);
        $import->forceFill(['stored_path' => $to])->save();
    }
}
