<?php

declare(strict_types=1);

namespace App\Support\Examinations;

use App\Enums\Institution\LevelEnum;

/**
 * Maps the free-text "COURSE LEVEL" cell of a HEXCO Individual Statement of Results
 * (e.g. "N.C.", "National Diploma") onto a LevelEnum case.
 */
final class HexcoCourseLevelMatcher
{
    /** @var array<string, LevelEnum> keyed by normalised statement text */
    private const ALIASES = [
        'NC' => LevelEnum::NC,
        'NATIONALCERTIFICATE' => LevelEnum::NC,
        'ND' => LevelEnum::ND,
        'NATIONALDIPLOMA' => LevelEnum::ND,
        'HND' => LevelEnum::HND,
        'HIGHERNATIONALDIPLOMA' => LevelEnum::HND,
        'BTECH' => LevelEnum::BTECH,
        'BACHELOROFTECHNOLOGY' => LevelEnum::BTECH,
        'SDP' => LevelEnum::SDP,
        'SKILLSDEVELOPMENTPROGRAM' => LevelEnum::SDP,
        'SKILLSDEVELOPMENTPROGRAMME' => LevelEnum::SDP,
    ];

    public static function match(?string $courseLevel): ?LevelEnum
    {
        $normalized = self::normalize($courseLevel);

        if ($normalized === '') {
            return null;
        }

        return self::ALIASES[$normalized] ?? null;
    }

    /**
     * Strip punctuation, whitespace and case so "N.C.", "n c" and "National Certificate"
     * all collapse onto a single lookup key.
     */
    private static function normalize(?string $courseLevel): string
    {
        if ($courseLevel === null) {
            return '';
        }

        $upper = strtoupper(trim($courseLevel));

        return (string) preg_replace('/[^A-Z0-9]/', '', $upper);
    }
}
