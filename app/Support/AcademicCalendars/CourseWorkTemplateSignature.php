<?php

namespace App\Support\AcademicCalendars;

use JsonException;

/**
 * Signs the context of a downloaded course work template (class config, module, students, editable
 * columns, generator, time) so an upload can be checked against what the system actually issued.
 * Marks are not signed: they are the only thing a lecturer is meant to change.
 */
final class CourseWorkTemplateSignature
{
    public const int VERSION = 1;

    public const string META_SHEET_TITLE = '_meta';

    /** Cell prefixes keep spreadsheet value binding from ever reading the strings as numbers. */
    public const string PAYLOAD_PREFIX = 'payload:';

    public const string SIGNATURE_PREFIX = 'sig:';

    /**
     * @param  array<string, mixed>  $payload
     * @return array{payload: string, signature: string}
     *
     * @throws JsonException
     */
    public static function sign(array $payload): array
    {
        $encoded = base64_encode(json_encode(['v' => self::VERSION, ...$payload], JSON_THROW_ON_ERROR));

        return [
            'payload' => $encoded,
            'signature' => hash_hmac('sha256', $encoded, self::key()),
        ];
    }

    /**
     * @return array<string, mixed>|null Null when the signature does not match or the payload is unreadable.
     */
    public static function verify(string $encodedPayload, string $signature): ?array
    {
        if ($encodedPayload === '' || $signature === '') {
            return null;
        }

        if (! hash_equals(hash_hmac('sha256', $encodedPayload, self::key()), $signature)) {
            return null;
        }

        $json = base64_decode($encodedPayload, true);

        if ($json === false) {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) && ($decoded['v'] ?? null) === self::VERSION ? $decoded : null;
    }

    private static function key(): string
    {
        return hash('sha256', (string) config('app.key').'|coursework-template', true);
    }
}
