<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

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
    ) {}

    /**
     * @return array{
     *     previewToken: string,
     *     fileName: string,
     *     summary: array<string, int>,
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
            'rows' => $analysis['rows'],
        ];
    }

    /**
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     * }
     */
    public function processFromPreview(int $tenantId, string $previewToken): array
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
            );
        } finally {
            Cache::forget(self::PREVIEW_CACHE_PREFIX.$previewToken);
            Storage::disk('ingest')->delete($storedPath);
        }
    }

    /**
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     * }
     */
    private function processStoredFile(int $tenantId, string $fullPath, string $originalFilename): array
    {
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
            $analysis = $this->analyseFile($fullPath, $tenantId, dryRun: false);

            if ($analysis['summary']['creates'] + $analysis['summary']['updates'] === 0) {
                throw ValidationException::withMessages([
                    'preview_token' => [__('trans.maintenance_staff_import_no_rows')],
                ]);
            }

            $rowsSucceeded = 0;
            $rowsFailed = 0;

            foreach ($analysis['operations'] as $operation) {
                if ($operation['status'] === 'skipped' || $operation['status'] === 'failed') {
                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        $operation['status'],
                        (array) ($operation['raw'] ?? []),
                        $operation['errors'] ?? null,
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
     * @return array{
     *     summary: array{total: int, succeeded: int, failed: int, skipped: int, creates: int, updates: int},
     *     operations: list<array<string, mixed>>,
     *     rows: list<array<string, mixed>>,
     * }
     */
    private function analyseFile(string $fullPath, int $tenantId, bool $dryRun): array
    {
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
            $analysis = $importer->analyseRow($rawRow);
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
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
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
