<?php

namespace App\Importers\Finance;

use App\Models\Finance\FinanceExchangeRate;
use App\Rules\ValidExchangeRateDate;
use App\Rules\ValidExchangeRateRate;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\Enums\DuplicateStrategy;
use LaravelIngest\Enums\SourceType;
use LaravelIngest\IngestConfig;

class FinanceExchangeRateImporter implements IngestDefinition
{
    public function getConfig(): IngestConfig
    {
        return IngestConfig::for(FinanceExchangeRate::class)
            // `ingest:run --file=/path/to/file` passes a filesystem path string, so
            // we must use the filesystem handler (not the upload handler).
            ->fromSource(SourceType::FILESYSTEM, [
                // `FilesystemHandler` uses `Storage::disk($disk)->exists($path)`.
                // Our Excel lives at `storage/data/...` (project-root `storage/`),
                // so we point ingest to a disk whose root is the project directory.
                'disk' => 'ingest',
            ])
            ->keyedBy('date') // Identify records by date
            ->onDuplicate(DuplicateStrategy::UPDATE) // Update if exists
            // Map CSV columns to DB attributes
            ->map('date', 'date')
            ->map('currency_from', 'currency_from')
            ->map('currency_to', 'currency_to')
            ->map('rate', 'rate')
            // Validate rows before processing
            ->validate([
                'date' => ['required', new ValidExchangeRateDate],
                'currency_from' => 'required|string',
                'currency_to' => 'required|string',
                'rate' => ['required', new ValidExchangeRateRate],
            ]);
    }
}
