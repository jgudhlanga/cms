<?php

namespace App\Console\Commands\Examinations;

use App\Services\Examinations\ExaminationImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Watcher\Watch;
use Throwable;

class WatchExaminationDumpsCommand extends Command
{
    protected $signature = 'examinations:watch
                            {--tenant= : Tenant ID for watcher-sourced imports}
                            {--once : Process existing inbox files once and exit (no watcher loop)}';

    protected $description = 'Watch the examinations inbox folder and queue imports for new dump files';

    public function handle(ExaminationImportService $service): int
    {
        $disk = Storage::disk('local');
        $inboxRelative = (string) config('examinations.inbox_path', 'examinations/inbox');
        $disk->makeDirectory($inboxRelative);
        $disk->makeDirectory((string) config('examinations.processing_path', 'examinations/processing'));
        $disk->makeDirectory((string) config('examinations.processed_path', 'examinations/processed'));
        $disk->makeDirectory((string) config('examinations.failed_path', 'examinations/failed'));

        $inboxAbsolute = $disk->path($inboxRelative);
        $tenantId = $this->option('tenant') !== null ? (int) $this->option('tenant') : null;

        $this->info("Watching examination dumps in: {$inboxAbsolute}");

        foreach ($this->existingInboxFiles($inboxAbsolute) as $path) {
            $this->queueFile($service, $path, $tenantId);
        }

        if ($this->option('once')) {
            return self::SUCCESS;
        }

        Watch::path($inboxAbsolute)
            ->onFileCreated(function (string $path) use ($service, $tenantId): void {
                $this->queueFile($service, $path, $tenantId);
            })
            ->start();

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function existingInboxFiles(string $inboxAbsolute): array
    {
        if (! is_dir($inboxAbsolute)) {
            return [];
        }

        $allowed = config('examinations.allowed_extensions', ['xlsx', 'xls', 'csv']);
        $files = [];

        foreach (scandir($inboxAbsolute) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $inboxAbsolute.DIRECTORY_SEPARATOR.$entry;

            if (! is_file($path)) {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed, true)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    private function queueFile(ExaminationImportService $service, string $path, ?int $tenantId): void
    {
        $allowed = config('examinations.allowed_extensions', ['xlsx', 'xls', 'csv']);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($ext, $allowed, true)) {
            return;
        }

        if (! $this->waitUntilSettled($path)) {
            $this->warn("Timed out waiting for file to settle: {$path}");

            return;
        }

        try {
            $import = $service->startFromWatcherPath($path, $tenantId);
            $this->info("Queued examination import #{$import->id} for ".basename($path));
        } catch (Throwable $exception) {
            Log::error('Examination watcher import failed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
            $this->error("Failed to queue {$path}: ".$exception->getMessage());
        }
    }

    private function waitUntilSettled(string $path): bool
    {
        $settleSeconds = max(1, (int) config('examinations.watcher_settle_seconds', 3));
        $maxWait = max($settleSeconds, (int) config('examinations.watcher_settle_max_wait_seconds', 120));
        $deadline = time() + $maxWait;
        $lastSize = -1;
        $stableSince = null;

        while (time() < $deadline) {
            if (! is_file($path)) {
                return false;
            }

            clearstatcache(true, $path);
            $size = filesize($path);

            if ($size === false) {
                usleep(250_000);

                continue;
            }

            if ($size === $lastSize) {
                if ($stableSince === null) {
                    $stableSince = time();
                }

                if ((time() - $stableSince) >= $settleSeconds) {
                    return true;
                }
            } else {
                $lastSize = $size;
                $stableSince = time();
            }

            usleep(250_000);
        }

        return is_file($path);
    }
}
