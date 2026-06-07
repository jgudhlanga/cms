<?php

declare(strict_types=1);

namespace App\Support;

final class RecipientEmailParser
{
    /**
     * @param  array<int, mixed>|string|null  $value
     * @return list<string>
     */
    public static function parse(array|string|null $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $parts = is_array($value) ? $value : [$value];

        $emails = [];

        foreach ($parts as $part) {
            if (! is_string($part) || $part === '') {
                continue;
            }

            $emails = [...$emails, ...self::split($part)];
        }

        return array_values(array_unique($emails));
    }

    /**
     * @return list<string>
     */
    private static function split(string $value): array
    {
        return array_values(array_filter(array_map(
            trim(...),
            preg_split('/[\s,;]+/', $value) ?: [],
        )));
    }
}
