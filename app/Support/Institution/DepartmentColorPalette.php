<?php

namespace App\Support\Institution;

final class DepartmentColorPalette
{
    /** @var list<string> */
    public const COLORS = [
        '#2563EB',
        '#DC2626',
        '#059669',
        '#D97706',
        '#7C3AED',
        '#DB2777',
        '#0891B2',
        '#4F46E5',
        '#EA580C',
        '#0D9488',
        '#9333EA',
        '#BE123C',
        '#0284C7',
        '#65A30D',
        '#C026D3',
        '#E11D48',
        '#0369A1',
        '#15803D',
        '#A21CAF',
        '#B45309',
        '#1D4ED8',
        '#047857',
        '#6D28D9',
        '#B91C1C',
    ];

    /** @var list<int> */
    private const LIGHTNESS_OFFSETS = [-18, -12, -6, 6, 12, 18];

    /**
     * @param  list<string>  $usedColors
     */
    public static function nextColor(array $usedColors = []): string
    {
        $used = self::normalizeUsed($usedColors);

        foreach (self::COLORS as $color) {
            $normalized = self::normalize($color);

            if (! in_array($normalized, $used, true)) {
                return $normalized;
            }
        }

        foreach (self::shadeCandidates() as $candidate) {
            if (! in_array($candidate, $used, true)) {
                return $candidate;
            }
        }

        $sequence = count($used);

        do {
            $candidate = self::generateDistinctColor($sequence++);
        } while (in_array($candidate, $used, true));

        return $candidate;
    }

    public static function generateDistinctColor(int $sequence): string
    {
        $hue = fmod($sequence * 137.508, 360);
        $saturation = 58 + ($sequence % 4) * 7;
        $lightness = 38 + ($sequence % 5) * 5;

        return self::hslToHex($hue, $saturation, $lightness);
    }

    public static function normalize(?string $color): string
    {
        return strtoupper(trim((string) $color));
    }

    public static function isValid(?string $color): bool
    {
        return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
    }

    /**
     * @param  list<string>  $usedColors
     * @return list<string>
     */
    public static function normalizeUsed(array $usedColors): array
    {
        return array_values(array_unique(array_map(
            static fn (string $color): string => self::normalize($color),
            array_filter($usedColors, static fn (mixed $color): bool => is_string($color) && $color !== ''),
        )));
    }

    /**
     * @return list<string>
     */
    private static function shadeCandidates(): array
    {
        $candidates = [];

        foreach (self::COLORS as $baseColor) {
            foreach (self::LIGHTNESS_OFFSETS as $offset) {
                $candidates[] = self::adjustLightness($baseColor, $offset);
            }
        }

        return $candidates;
    }

    private static function adjustLightness(string $hexColor, int $lightnessDelta): string
    {
        [$red, $green, $blue] = self::hexToRgb($hexColor);
        [$hue, $saturation, $lightness] = self::rgbToHsl($red, $green, $blue);

        $lightness = max(18, min(82, $lightness + $lightnessDelta));

        return self::hslToHex($hue, $saturation, $lightness);
    }

    private static function hslToHex(float $hue, float $saturation, float $lightness): string
    {
        $saturation /= 100;
        $lightness /= 100;

        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $huePrime = fmod($hue, 360) / 60;
        $second = $chroma * (1 - abs(fmod($huePrime, 2) - 1));
        $match = $lightness - ($chroma / 2);

        [$red, $green, $blue] = match (true) {
            $huePrime < 1 => [$chroma, $second, 0],
            $huePrime < 2 => [$second, $chroma, 0],
            $huePrime < 3 => [0, $chroma, $second],
            $huePrime < 4 => [0, $second, $chroma],
            $huePrime < 5 => [$second, 0, $chroma],
            default => [$chroma, 0, $second],
        };

        return sprintf(
            '#%02X%02X%02X',
            (int) round(($red + $match) * 255),
            (int) round(($green + $match) * 255),
            (int) round(($blue + $match) * 255),
        );
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function rgbToHsl(int $red, int $green, int $blue): array
    {
        $red /= 255;
        $green /= 255;
        $blue /= 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, round($lightness * 100, 2)];
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5
            ? $delta / (2 - $max - $min)
            : $delta / ($max + $min);

        $hue = match ($max) {
            $red => fmod((($green - $blue) / $delta) + ($green < $blue ? 6 : 0), 6),
            $green => (($blue - $red) / $delta) + 2,
            default => (($red - $green) / $delta) + 4,
        } * 60;

        return [round($hue, 2), round($saturation * 100, 2), round($lightness * 100, 2)];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hexColor): array
    {
        $hex = ltrim(self::normalize($hexColor), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
