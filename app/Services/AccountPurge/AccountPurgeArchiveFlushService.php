<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Models\AccountPurge\AccountPurgeArchive;
use App\Models\Students\StudentNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AccountPurgeArchiveFlushService
{
    public function flushExpired(): int
    {
        $archives = AccountPurgeArchive::query()
            ->whereNull('flushed_at')
            ->where('flush_after', '<=', now())
            ->get();

        $flushed = 0;

        foreach ($archives as $archive) {
            $this->flushArchive($archive);
            $flushed++;
        }

        return $flushed;
    }

    public function flushArchive(AccountPurgeArchive $archive): void
    {
        DB::transaction(function () use ($archive): void {
            $this->deleteArchivedMediaFiles($archive->payload ?? []);

            $noteId = $archive->student_note_id;

            $archive->update([
                'payload' => [],
                'flushed_at' => now(),
                'student_note_id' => null,
            ]);

            if ($noteId !== null) {
                StudentNote::query()->whereKey($noteId)->forceDelete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deleteArchivedMediaFiles(array $payload): void
    {
        $paths = $this->collectMediaPaths($payload);

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && File::exists($path)) {
                File::delete($path);

                continue;
            }

            if (is_string($path) && str_contains($path, '/')) {
                $diskPath = $this->resolveStorageRelativePath($path);
                if ($diskPath !== null) {
                    Storage::disk('public')->delete($diskPath);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function collectMediaPaths(array $payload): array
    {
        $paths = [];

        $walker = function (mixed $value) use (&$paths, &$walker): void {
            if (! is_array($value)) {
                return;
            }

            if (isset($value['path']) && is_string($value['path'])) {
                $paths[] = $value['path'];
            }

            foreach ($value as $item) {
                if (is_array($item)) {
                    $walker($item);
                }
            }
        };

        $walker($payload);

        return array_values(array_unique($paths));
    }

    private function resolveStorageRelativePath(string $path): ?string
    {
        $publicRoot = Storage::disk('public')->path('');

        if (str_starts_with($path, $publicRoot)) {
            return ltrim(substr($path, strlen($publicRoot)), '/');
        }

        return null;
    }
}
