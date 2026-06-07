<?php

declare(strict_types=1);

namespace App\Support\Exports;

use Illuminate\Support\Facades\Storage;

class CsvExportWriter
{
    /**
     * @param  list<string>  $headers
     * @param  callable(resource): void  $writeRows
     */
    public function write(string $relativePath, array $headers, callable $writeRows): string
    {
        $disk = Storage::disk('local');
        $absolutePath = $disk->path($relativePath);
        $directoryPath = dirname($absolutePath);

        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $tempPath = $absolutePath.'.tmp';

        $handle = fopen($tempPath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open temporary export file at {$tempPath}.");
        }

        try {
            fputcsv($handle, $headers);
            $writeRows($handle);
        } finally {
            fclose($handle);
        }

        if (! rename($tempPath, $absolutePath)) {
            @unlink($tempPath);

            throw new \RuntimeException("Unable to move export file to {$absolutePath}.");
        }

        return $relativePath;
    }
}
