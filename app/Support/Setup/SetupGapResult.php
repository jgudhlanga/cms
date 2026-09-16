<?php

declare(strict_types=1);

namespace App\Support\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Enums\Setup\SetupGapSeverityEnum;

/**
 * One problem found by a check, before it is stored. The fingerprint identifies the problem itself (not
 * the run), so re-detecting it updates the open row instead of raising a duplicate.
 */
final readonly class SetupGapResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public SetupGapCheckEnum $check,
        public int $tenantId,
        public string $title,
        public ?string $body = null,
        public ?int $institutionDepartmentId = null,
        public ?string $url = null,
        public array $meta = [],
        public ?SetupGapSeverityEnum $severity = null,
        private ?string $fingerprintKey = null,
    ) {}

    public function severity(): SetupGapSeverityEnum
    {
        return $this->severity ?? $this->check->severity();
    }

    /**
     * Stable identity for this problem: the check, the tenant, the department and whatever the check
     * considers the subject (a course level, a class config, a hostel).
     */
    public function fingerprint(): string
    {
        return hash('sha256', implode('|', [
            $this->check->value,
            $this->tenantId,
            $this->institutionDepartmentId ?? 0,
            $this->fingerprintKey ?? $this->title,
        ]));
    }
}
