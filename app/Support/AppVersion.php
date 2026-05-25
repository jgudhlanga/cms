<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class AppVersion
{
    public function resolve(): string
    {
        $fromConfig = trim((string) (config('app.version') ?? ''));
        if ($fromConfig !== '') {
            return $fromConfig;
        }

        $versionPath = base_path('VERSION');
        if (is_file($versionPath)) {
            $contents = @file_get_contents($versionPath);
            if ($contents !== false) {
                $fromFile = trim($contents);
                if ($fromFile !== '') {
                    return $fromFile;
                }
            }
        }

        if (config('app.env') === 'local' && is_dir(base_path('.git'))) {
            return Cache::remember(
                'app.git_short_sha',
                now()->addHour(),
                function (): string {
                    $process = new Process(['git', 'rev-parse', '--short', 'HEAD'], base_path());
                    $process->run();

                    if (! $process->isSuccessful()) {
                        return 'local';
                    }

                    $sha = trim($process->getOutput());

                    return $sha !== '' ? $sha : 'local';
                }
            );
        }

        return 'unknown';
    }
}
