<?php

declare(strict_types=1);

namespace App\Enums\Console;

enum ConsoleRunStatusEnum: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => __('console.status_queued'),
            self::RUNNING => __('console.status_running'),
            self::SUCCEEDED => __('console.status_succeeded'),
            self::FAILED => __('console.status_failed'),
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::SUCCEEDED, self::FAILED => true,
            self::QUEUED, self::RUNNING => false,
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::QUEUED => 'slate',
            self::RUNNING => 'blue',
            self::SUCCEEDED => 'green',
            self::FAILED => 'red',
        };
    }
}
