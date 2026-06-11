<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

use App\DTO\Institution\StaffImportRowDto;
use App\Importers\Maintenance\StaffImporter;
use App\Models\Maintenance\StaffImportLog;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IStaffRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LaravelIngest\Enums\IngestStatus;
use LaravelIngest\Models\IngestRow;
use LaravelIngest\Models\IngestRun;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

class StaffImportService
{
    private const string PREVIEW_CACHE_PREFIX = 'staff-import-preview:';

    private const int PREVIEW_TTL_MINUTES = 30;

    public function __construct(
        private readonly IStaffRepository $staffRepository,
        private readonly StaffImportLookups $lookups,
    ) {}

    /**
     * @return array{
     *     previewToken: string,
     *     fileName: string,
     *     summary: array<string, int>,
     *     lookups: array<string, list<array{value: int, label: string}>>,
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(int $tenantId, UploadedFile $file): array
    {
        $storedPath = $file->store('staff-imports/previews', 'ingest');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        $analysis = $this->analyseFile($fullPath, $tenantId, dryRun: true);

        $user = Auth::user();
        $previewToken = Str::random(40);

        Cache::put(
            self::PREVIEW_CACHE_PREFIX.$previewToken,
            [
                'path' => $storedPath,
                'tenant_id' => $tenantId,
                'user_id' => $user instanceof User ? $user->id : null,
                'original_filename' => $file->getClientOriginalName(),
            ],
            now()->addMinutes(self::PREVIEW_TTL_MINUTES),
        );

        return [
            'previewToken' => $previewToken,
            'fileName' => $file->getClientOriginalName(),
            'summary' => $analysis['summary'],
            'lookups' => $this->lookups->optionsForPreview($tenantId),
            'rows' => $analysis['rows'],
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     *     failedRows: list<array{
     *         rowNumber: int,
     *         employeeNumber: string|null,
     *         fullName: string|null,
     *         email: string|null,
     *         errors: list<string>,
     *     }>,
     * }
     */
    public function processFromPreview(int $tenantId, string $previewToken, ?array $rowCorrections = null): array
    {
        $preview = Cache::get(self::PREVIEW_CACHE_PREFIX.$previewToken);

        if (! is_array($preview)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('trans.maintenance_staff_import_preview_expired')],
            ]);
        }

        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        if ($userId === null || (int) ($preview['user_id'] ?? 0) !== $userId) {
            throw ValidationException::withMessages([
                'preview_token' => [__('trans.maintenance_staff_import_preview_expired')],
            ]);
        }

        if ((int) ($preview['tenant_id'] ?? 0) !== $tenantId) {
            throw ValidationException::withMessages([
                'preview_token' => [__('trans.maintenance_staff_import_preview_mismatch')],
            ]);
        }

        $storedPath = (string) ($preview['path'] ?? '');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        if (! Storage::disk('ingest')->exists($storedPath)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('trans.maintenance_staff_import_preview_expired')],
            ]);
        }

        try {
            return $this->processStoredFile(
                $tenantId,
                $fullPath,
                (string) ($preview['original_filename'] ?? 'import.xlsx'),
                $rowCorrections,
            );
        } finally {
            Cache::forget(self::PREVIEW_CACHE_PREFIX.$previewToken);
            Storage::disk('ingest')->delete($storedPath);
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     *     failedRows: list<array{
     *         rowNumber: int,
     *         employeeNumber: string|null,
     *         fullName: string|null,
     *         email: string|null,
     *         errors: list<string>,
     *     }>,
     * }
     */
    private function processStoredFile(
        int $tenantId,
        string $fullPath,
        string $originalFilename,
        ?array $rowCorrections = null,
    ): array {
        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        $ingestRun = IngestRun::query()->create([
            'importer' => StaffImporter::IMPORTER_NAME,
            'user_id' => $userId,
            'status' => IngestStatus::PROCESSING,
            'original_filename' => $originalFilename,
            'processed_filepath' => $fullPath,
        ]);

        try {
            $analysis = $this->analyseFile($fullPath, $tenantId, dryRun: false, rowCorrections: $rowCorrections);

            if ($analysis['summary']['creates'] + $analysis['summary']['updates'] === 0) {
                throw ValidationException::withMessages([
                    'preview_token' => [__('trans.maintenance_staff_import_no_rows')],
                ]);
            }

            $rowsSucceeded = 0;
            $rowsFailed = 0;
            $failedRows = [];

            foreach ($analysis['operations'] as $operation) {
                if ($operation['status'] === 'skipped') {
                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        $operation['status'],
                        (array) ($operation['raw'] ?? []),
                        $operation['errors'] ?? null,
                    );

                    continue;
                }

                if ($operation['status'] === 'failed') {
                    $rowsFailed++;
                    $errors = is_array($operation['errors'] ?? null) ? $operation['errors'] : null;
                    $failedRows[] = $this->buildFailedRowPayload($operation, $errors);

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        'failed',
                        (array) ($operation['raw'] ?? []),
                        $errors,
                    );

                    continue;
                }

                try {
                    /** @var StaffImportRowDto $dto */
                    $dto = $operation['dto'];
                    $this->staffRepository->upsertFromImport($dto);
                    $rowsSucceeded++;

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        'success',
                        (array) ($operation['raw'] ?? []),
                        null,
                    );
                } catch (Throwable $exception) {
                    $rowsFailed++;

                    $errors = $exception instanceof ValidationException
                        ? $exception->errors()
                        : ['import' => [$exception->getMessage()]];

                    $failedRows[] = $this->buildFailedRowPayload($operation, $errors);

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        'failed',
                        (array) ($operation['raw'] ?? []),
                        $errors,
                    );
                }
            }

            $summary = $analysis['summary'];
            $rowsSkipped = $summary['skipped'];

            $ingestRun->update([
                'status' => $rowsFailed > 0 ? IngestStatus::COMPLETED_WITH_ERRORS : IngestStatus::COMPLETED,
                'total_rows' => $summary['total'],
                'processed_rows' => $summary['total'],
                'successful_rows' => $rowsSucceeded,
                'failed_rows' => $rowsFailed,
                'completed_at' => now(),
                'summary' => [
                    'skipped' => $rowsSkipped,
                ],
            ]);

            $importLog = StaffImportLog::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'ingest_run_id' => $ingestRun->id,
                'original_filename' => $originalFilename,
                'rows_total' => $summary['total'],
                'rows_succeeded' => $rowsSucceeded,
                'rows_failed' => $rowsFailed,
                'rows_skipped' => $rowsSkipped,
                'created_at' => now(),
            ]);

            return [
                'ingestRunId' => $ingestRun->id,
                'importLogId' => $importLog->id,
                'rowsTotal' => $summary['total'],
                'rowsSucceeded' => $rowsSucceeded,
                'rowsFailed' => $rowsFailed,
                'rowsSkipped' => $rowsSkipped,
                'failedRows' => $failedRows,
            ];
        } catch (Throwable $exception) {
            $ingestRun->update([
                'status' => IngestStatus::FAILED,
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @return array{
     *     summary: array{total: int, succeeded: int, failed: int, skipped: int, creates: int, updates: int},
     *     operations: list<array<string, mixed>>,
     *     rows: list<array<string, mixed>>,
     * }
     */
    private function analyseFile(
        string $fullPath,
        int $tenantId,
        bool $dryRun,
        ?array $rowCorrections = null,
    ): array {
        $rawRows = $this->readRawRows($fullPath);
        $headerRowIndex = $this->detectHeaderRowIndex($rawRows);

        if ($headerRowIndex === null) {
            throw ValidationException::withMessages([
                'file' => [__('trans.maintenance_staff_import_invalid_format')],
            ]);
        }

        $importer = new StaffImporter($tenantId);
        $operations = [];
        $rows = [];

        $summary = [
            'total' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'creates' => 0,
            'updates' => 0,
        ];

        $rowNumber = 0;

        for ($index = $headerRowIndex + 1; $index < count($rawRows); $index++) {
            $rawRow = $rawRows[$index];

            if (! $this->rowHasContent($rawRow)) {
                continue;
            }

            $rowNumber++;
            $corrections = $this->correctionsForRow($rowCorrections, $rowNumber);
            $analysis = $importer->analyseRow($rawRow, $corrections);
            $action = $analysis['action'];

            if ($action === 'skip_empty') {
                $summary['skipped']++;
            } elseif ($action === 'fail') {
                $summary['failed']++;
            } elseif ($action === 'create') {
                $summary['creates']++;
                $summary['succeeded']++;
            } elseif ($action === 'update') {
                $summary['updates']++;
                $summary['succeeded']++;
            }

            $summary['total']++;

            $rows[] = [
                'rowNumber' => $rowNumber,
                'employeeNumber' => $analysis['display']['employeeNumber'] ?? null,
                'fullName' => $analysis['display']['fullName'] ?? null,
                'email' => $analysis['display']['email'] ?? null,
                'department' => $analysis['display']['department'] ?? null,
                'action' => $action,
                'errors' => $analysis['errors'],
                'fields' => $analysis['fields'],
                'needsReview' => $analysis['needsReview'],
            ];

            $operations[] = [
                'rowNumber' => $rowNumber,
                'status' => match ($action) {
                    'skip_empty' => 'skipped',
                    'fail' => 'failed',
                    default => $dryRun ? $action : $action,
                },
                'dto' => $analysis['dto'],
                'errors' => $analysis['errors'],
                'raw' => StaffImporter::rowToAssociative($rawRow),
            ];
        }

        return [
            'summary' => $summary,
            'operations' => $operations,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @return array<string, mixed>|null
     */
    private function correctionsForRow(?array $rowCorrections, int $rowNumber): ?array
    {
        if ($rowCorrections === null) {
            return null;
        }

        $corrections = $rowCorrections[$rowNumber] ?? $rowCorrections[(string) $rowNumber] ?? null;

        return is_array($corrections) ? $corrections : null;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readRawRows(string $fullPath): array
    {
        $rows = [];

        SimpleExcelReader::create($fullPath)->noHeaderRow()->getRows()->each(function (array $row) use (&$rows): void {
            $rows[] = array_values($row);
        });

        return $rows;
    }

    /**
     * @param  list<list<mixed>>  $rawRows
     */
    private function detectHeaderRowIndex(array $rawRows): ?int
    {
        $limit = min(count($rawRows), 50);

        for ($index = 0; $index < $limit; $index++) {
            if (StaffImporter::isHeaderRow($rawRows[$index])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $value) {
            $normalized = StaffImporter::normalizeCellValue($value);

            if ($normalized !== null && $normalized !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, list<string>>|null  $errors
     * @return array{
     *     rowNumber: int,
     *     employeeNumber: string|null,
     *     fullName: string|null,
     *     email: string|null,
     *     errors: list<string>,
     * }
     */
    private function buildFailedRowPayload(array $operation, ?array $errors): array
    {
        $raw = (array) ($operation['raw'] ?? []);
        $dto = $operation['dto'] ?? null;

        $employeeNumber = isset($raw['EMPLOYEE_NUMBER']) ? (string) $raw['EMPLOYEE_NUMBER'] : null;
        $email = $dto instanceof StaffImportRowDto
            ? $dto->email
            : (isset($raw['EMAIL']) ? (string) $raw['EMAIL'] : null);

        $fullName = trim(implode(' ', array_filter([
            $raw['FIRST_NAME'] ?? null,
            $raw['MIDDLE_NAME'] ?? null,
            $raw['LAST_NAME'] ?? null,
        ])));

        return [
            'rowNumber' => (int) $operation['rowNumber'],
            'employeeNumber' => $employeeNumber !== '' ? $employeeNumber : null,
            'fullName' => $fullName !== '' ? $fullName : null,
            'email' => $email !== '' ? $email : null,
            'errors' => $this->flattenRowErrors($errors),
        ];
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     * @return list<string>
     */
    private function flattenRowErrors(?array $errors): array
    {
        if ($errors === null) {
            return [];
        }

        $messages = [];

        foreach ($errors as $fieldMessages) {
            if (! is_array($fieldMessages)) {
                continue;
            }

            foreach ($fieldMessages as $message) {
                if (is_string($message) && $message !== '') {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, list<string>>|null  $errors
     */
    private function recordIngestRow(
        IngestRun $ingestRun,
        int $rowNumber,
        string $status,
        array $raw,
        ?array $errors,
    ): void {
        IngestRow::query()->create([
            'ingest_run_id' => $ingestRun->id,
            'row_number' => $rowNumber,
            'status' => $status,
            'raw_data' => $raw,
            'errors' => $errors,
        ]);
    }
}
