<?php

declare(strict_types=1);

namespace App\Ingest;

use Generator;
use Illuminate\Support\Facades\Storage;
use LaravelIngest\Exceptions\SourceException;
use LaravelIngest\IngestConfig;
use LaravelIngest\Sources\FilesystemHandler;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

/**
 * Fixes an upstream issue where the default FilesystemHandler converts the
 * file path to an absolute path (via realpath) and then passes that absolute
 * path into Laravel's Storage adapter, which expects a path relative to the
 * disk root.
 */
class FixedFilesystemHandler extends FilesystemHandler
{
    /**
     * @throws SourceException
     */
    public function read(IngestConfig $config, mixed $payload = null): Generator
    {
        $disk = $config->sourceOptions['disk'] ?? $config->disk;

        $resolvedPath = is_string($payload) && ! empty($payload)
            ? $payload
            : ($config->sourceOptions['path'] ?? null);

        if (! $resolvedPath) {
            throw new SourceException(
                'The filesystem source is missing the "path" option. '.
                    'Please ensure you pass ["path" => "/path/to/file.csv"] when defining ->fromSource() or provide it via command argument.'
            );
        }

        $this->path = $resolvedPath;

        $normalizedPath = str_replace('\\', '/', $this->path);
        if (str_contains($normalizedPath, '../')) {
            throw new SourceException('Invalid file path detected for security reasons.');
        }

        $realPath = realpath($this->path);
        $diskRoot = Storage::disk($disk)->path('');
        $diskRootReal = realpath($diskRoot);
        $allowedRoots = array_filter([$diskRootReal, realpath(base_path())]);

        if ($realPath !== false && ! empty($allowedRoots)) {
            $isAllowed = false;
            foreach ($allowedRoots as $root) {
                if (str_starts_with($realPath, $root)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (! $isAllowed) {
                throw new SourceException('Invalid file path detected for security reasons.');
            }
        }

        // Convert to a disk-relative path so Storage can resolve it correctly.
        $storagePath = $this->path;
        if ($realPath !== false && $diskRootReal !== false && str_starts_with($realPath, $diskRootReal)) {
            $storagePath = ltrim(substr($realPath, strlen($diskRootReal)), DIRECTORY_SEPARATOR);
        }

        if (! Storage::disk($disk)->exists($storagePath)) {
            $storagePath = $this->resolveFallbackStoragePath($storagePath, $disk);
        }

        if (! Storage::disk($disk)->exists($storagePath)) {
            throw new SourceException(
                sprintf(
                    "We could not find the file at '%s' using the disk '%s'. ".
                        'Please check the path and ensure the disk is correctly configured in filesystems.php.',
                    $storagePath,
                    $disk
                )
            );
        }

        $this->path = $storagePath;

        $fullPath = Storage::disk($disk)->path($storagePath);
        $reader = SimpleExcelReader::create($fullPath);
        $rows = $reader->getRows();

        try {
            yield from $this->processRows($rows, $config);
        } catch (Throwable $e) {
            // Rethrow as-is so upstream can mark the ingest run as failed.
            throw $e;
        }
    }

    private function resolveFallbackStoragePath(string $storagePath, string $disk): string
    {
        $normalized = str_replace('\\', '/', $storagePath);

        if (str_contains($normalized, '/')) {
            return $storagePath;
        }

        $candidate = 'storage/data/'.$normalized;
        if (Storage::disk($disk)->exists($candidate)) {
            return $candidate;
        }

        return $storagePath;
    }
}
