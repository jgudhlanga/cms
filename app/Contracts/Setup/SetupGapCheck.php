<?php

declare(strict_types=1);

namespace App\Contracts\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;

/**
 * A single setup problem detector. Checks are pure reads: they report what they find and never repair
 * anything, so the nightly scan can run them all without side effects.
 */
interface SetupGapCheck
{
    /**
     * Container tag every check is registered under, so the scanner receives them all without knowing
     * which checks exist.
     */
    public const string TAG = 'setup-gap.checks';

    public function key(): SetupGapCheckEnum;

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable;
}
