<?php

declare(strict_types=1);

namespace App\Support\Console;

use App\Models\Console\ConsoleCommandRun;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\Output;

/**
 * Captures a command's output and persists it to the run row as it is produced,
 * so the UI can tail a command that is still running.
 *
 * Writes are buffered and flushed on a size or time threshold, because the
 * cache and queue both sit on the database here and a flush per line would be
 * a query per line.
 */
class ConsoleRunOutputStream extends Output
{
    private const FLUSH_BYTES = 8192;

    private const FLUSH_SECONDS = 1.0;

    private string $buffer = '';

    private float $lastFlushAt;

    public function __construct(
        private readonly ConsoleCommandRun $run,
    ) {
        parent::__construct(self::VERBOSITY_NORMAL, false, new OutputFormatter(false));

        $this->lastFlushAt = microtime(true);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message.($newline ? PHP_EOL : '');

        if (strlen($this->buffer) >= self::FLUSH_BYTES || (microtime(true) - $this->lastFlushAt) >= self::FLUSH_SECONDS) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->lastFlushAt = microtime(true);

        if ($this->buffer === '') {
            return;
        }

        $chunk = $this->buffer;
        $this->buffer = '';

        $this->run->forceFill([
            'output' => ($this->run->output ?? '').$chunk,
            'output_truncated' => false,
        ])->save();
    }
}
