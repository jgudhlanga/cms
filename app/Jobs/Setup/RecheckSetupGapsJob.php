<?php

declare(strict_types=1);

namespace App\Jobs\Setup;

use App\Services\Setup\SetupGapNotifier;
use App\Services\Setup\SetupGapScanner;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Re-runs the checks affected by a configuration change, so an alert clears itself as soon as the thing
 * it complains about is fixed — nobody has to run the nightly scan by hand.
 *
 * Unique per set of checks while it waits, so saving a screen that writes several rows at once (a course
 * with modes for five levels, say) re-checks once rather than five times. The lock lifts as soon as the
 * job starts, so a later edit is never swallowed by an earlier run.
 */
class RecheckSetupGapsJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    /**
     * @param  list<string>  $checkKeys
     */
    public function __construct(public array $checkKeys)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        $keys = $this->checkKeys;
        sort($keys);

        return 'setup-gaps:'.implode(',', $keys);
    }

    public function handle(SetupGapScanner $scanner, SetupGapNotifier $notifier): void
    {
        $summary = $scanner->scan($this->checkKeys);

        // Announce only what this run turned up. Anything already waiting belongs to the nightly sweep —
        // saving one screen must never fire the whole backlog at everyone.
        $notifier->notifyOnly($summary['raisedIds']);
    }
}
