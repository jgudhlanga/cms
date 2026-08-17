<?php

declare(strict_types=1);

namespace App\Importers\HMS;

use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

class HostelOccupantImporter
{
    /** @var list<string> */
    public const array COLUMNS = [
        'Student Number',
        'ID Number',
        'Passport Number',
        'Disability',
        'Hostel',
        'Floor',
        'Room',
        'Section',
    ];

    /** @var array<string, list<string>> */
    public const array HEADER_ALIASES = [
        'student_number' => ['STUDENT NUMBER'],
        'id_number' => [
            'ID NUMBER',
            'ID-NUMBER',
            'NATIONAL ID NUMBER',
            'NATIONAL ID',
            'ID-NUMBER / PASSPORT NUMBER',
            'ID NUMBER / PASSPORT NUMBER',
        ],
        'passport_number' => [
            'PASSPORT NUMBER',
            'PASSPORT',
        ],
        'disability' => ['DISABILITY'],
        'hostel' => ['HOSTEL'],
        'floor' => ['FLOOR'],
        'room' => ['ROOM'],
        'section' => ['SECTION'],
    ];

    /**
     * @return array{
     *     rows: list<array{
     *         rowNumber: int,
     *         studentNumber: string|null,
     *         idNumber: string|null,
     *         passportNumber: string|null,
     *         disability: string|null,
     *         hostel: string|null,
     *         floor: string|null,
     *         room: string|null,
     *         section: string|null,
     *     }>,
     *     headerRowNumber: int,
     * }
     */
    public function parse(string $filePath): array
    {
        $reader = SimpleExcelReader::create($filePath)->noHeaderRow();
        $sheetRows = [];

        $reader->getRows()->each(function (array $row) use (&$sheetRows): void {
            $sheetRows[] = array_values($row);
        });

        $reader->close();

        if ($sheetRows === []) {
            return [
                'rows' => [],
                'headerRowNumber' => 0,
            ];
        }

        $headerRowNumber = $this->detectHeaderRowNumber($sheetRows);

        if ($headerRowNumber === null) {
            throw new RuntimeException(__('hms.import_occupants_preview_failed'));
        }

        $columnMap = $this->mapColumns($sheetRows[$headerRowNumber - 1] ?? []);
        $parsedRows = [];

        foreach (array_slice($sheetRows, $headerRowNumber) as $index => $row) {
            $rowNumber = $headerRowNumber + $index + 1;
            $values = $this->extractRowValues($row, $columnMap);

            if ($this->isBlankRow($values)) {
                continue;
            }

            if ($this->isSecondaryHeaderRow($values)) {
                continue;
            }

            $parsedRows[] = [
                'rowNumber' => $rowNumber,
                'studentNumber' => $values['student_number'],
                'idNumber' => $values['id_number'],
                'passportNumber' => $values['passport_number'],
                'disability' => $values['disability'],
                'hostel' => $values['hostel'],
                'floor' => $values['floor'],
                'room' => $values['room'],
                'section' => $values['section'],
            ];
        }

        return [
            'rows' => $parsedRows,
            'headerRowNumber' => $headerRowNumber,
        ];
    }

    /**
     * @param  list<array<int, mixed>>  $sheetRows
     */
    private function detectHeaderRowNumber(array $sheetRows): ?int
    {
        foreach ($sheetRows as $index => $row) {
            $columnMap = $this->mapColumns($row);

            if (
                $columnMap['student_number'] !== null
                || $columnMap['id_number'] !== null
                || $columnMap['passport_number'] !== null
            ) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, int|null>
     */
    private function mapColumns(array $row): array
    {
        $normalizedHeaders = [];

        foreach (array_values($row) as $index => $value) {
            $normalizedHeaders[$index] = $this->normalizeHeader((string) $value);
        }

        $columnMap = [
            'student_number' => null,
            'id_number' => null,
            'passport_number' => null,
            'disability' => null,
            'hostel' => null,
            'floor' => null,
            'room' => null,
            'section' => null,
        ];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }

                if (in_array($header, $aliases, true)) {
                    $columnMap[$field] = $index;

                    break;
                }
            }
        }

        return $columnMap;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int|null>  $columnMap
     * @return array<string, string|null>
     */
    private function extractRowValues(array $row, array $columnMap): array
    {
        $values = array_values($row);

        return [
            'student_number' => $this->cellValue($values, $columnMap['student_number']),
            'id_number' => $this->cellValue($values, $columnMap['id_number']),
            'passport_number' => $this->cellValue($values, $columnMap['passport_number']),
            'disability' => $this->cellValue($values, $columnMap['disability']),
            'hostel' => $this->cellValue($values, $columnMap['hostel']),
            'floor' => $this->cellValue($values, $columnMap['floor']),
            'room' => $this->cellValue($values, $columnMap['room']),
            'section' => $this->cellValue($values, $columnMap['section']),
        ];
    }

    /**
     * @param  list<mixed>  $values
     */
    private function cellValue(array $values, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $values)) {
            return null;
        }

        $value = trim((string) $values[$index]);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        return $values['student_number'] === null
            && $values['id_number'] === null
            && $values['passport_number'] === null
            && $values['disability'] === null
            && $values['hostel'] === null
            && $values['floor'] === null
            && $values['room'] === null
            && $values['section'] === null;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    private function isSecondaryHeaderRow(array $values): bool
    {
        $studentNumber = strtoupper((string) $values['student_number']);
        $idNumber = strtoupper((string) $values['id_number']);
        $passportNumber = strtoupper((string) $values['passport_number']);

        return in_array($studentNumber, ['STUDENT NUMBER'], true)
            || in_array($idNumber, ['ID NUMBER', 'ID-NUMBER', 'ID-NUMBER / PASSPORT NUMBER'], true)
            || in_array($passportNumber, ['PASSPORT NUMBER', 'PASSPORT'], true);
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['_', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
