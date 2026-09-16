<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\Students\StudyPositionSourceEnum;
use App\Models\Institution\ProgrammeSemester;

final readonly class StudyPositionDetection
{
    public const FOUND = 'found';

    public const NONE = 'none';

    public const CONFLICT = 'conflict';

    /**
     * @param  array<string, mixed>  $evidence
     */
    private function __construct(
        public string $outcome,
        public ?ProgrammeSemester $phase = null,
        public ?StudyPositionSourceEnum $source = null,
        public array $evidence = [],
    ) {}

    /**
     * @param  array<string, mixed>  $evidence
     */
    public static function found(ProgrammeSemester $phase, StudyPositionSourceEnum $source, array $evidence): self
    {
        return new self(self::FOUND, $phase, $source, $evidence);
    }

    public static function none(): self
    {
        return new self(self::NONE);
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public static function conflict(array $evidence): self
    {
        return new self(self::CONFLICT, evidence: $evidence);
    }

    public function isFound(): bool
    {
        return $this->outcome === self::FOUND;
    }
}
