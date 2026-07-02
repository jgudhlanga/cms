<?php

namespace App\Services\AcademicCalendars;

class AcademicCalendarClassNameFormatter
{
    public function format(string $levelName, ?string $modeName, int $classNumber): string
    {
        $segments = [
            $this->normalizeSegment($levelName),
        ];

        $normalizedMode = $this->normalizeSegment($modeName);
        if ($normalizedMode !== '') {
            $segments[] = $normalizedMode;
        }

        $segments[] = (string) $classNumber;

        return implode('-', $segments);
    }

    /**
     * @param  iterable<string>  $classNames
     */
    public function extractHighestClassNumber(iterable $classNames, string $levelName, ?string $modeName): int
    {
        $highest = 0;

        foreach ($classNames as $className) {
            $number = $this->extractClassNumber((string) $className, $levelName, $modeName);

            if ($number !== null) {
                $highest = max($highest, $number);
            }
        }

        return $highest;
    }

    public function extractClassNumber(string $className, string $levelName, ?string $modeName): ?int
    {
        $canonicalBase = $this->formatBase($levelName, $modeName);
        $canonicalPattern = '/^'.preg_quote($canonicalBase, '/').'-(\d+)$/i';

        if (preg_match($canonicalPattern, $className, $matches)) {
            return (int) $matches[1];
        }

        $legacyBase = $this->legacyNameBase($levelName, $modeName);
        $legacyPattern = '/^'.preg_quote($legacyBase, '/').'\s-\s(\d+)(?:\s-\s\d+)?$/';

        if (preg_match($legacyPattern, $className, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function formatBase(string $levelName, ?string $modeName): string
    {
        $segments = [$this->normalizeSegment($levelName)];

        $normalizedMode = $this->normalizeSegment($modeName);
        if ($normalizedMode !== '') {
            $segments[] = $normalizedMode;
        }

        return implode('-', $segments);
    }

    private function legacyNameBase(string $levelName, ?string $modeName): string
    {
        $level = trim($levelName);
        $mode = trim((string) $modeName);

        return $mode !== ''
            ? $level.' - '.$mode
            : $level;
    }

    private function normalizeSegment(?string $segment): string
    {
        $segment = trim((string) $segment);

        if ($segment === '') {
            return '';
        }

        return strtoupper(str_replace(' ', '-', $segment));
    }
}
