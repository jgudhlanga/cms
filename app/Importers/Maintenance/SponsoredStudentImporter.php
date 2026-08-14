<?php

declare(strict_types=1);

namespace App\Importers\Maintenance;

use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

class SponsoredStudentImporter
{
    /** @var list<string> */
    public const array COLUMNS = [
        'Student Number',
        'Sponsor',
    ];

    /** @var array<string, list<string>> */
    public const array HEADER_ALIASES = [
        'student_number' => ['STUDENT NUMBER'],
        'sponsor' => ['SPONSOR', 'SPONSOR NAME'],
    ];

    /**
     * @return array{
     *     rows: list<array{
     *         rowNumber: int,
     *         studentNumber: string|null,
     *         sponsor: string|null,
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
            throw new RuntimeException(__('trans.maintenance_sponsored_students_import_preview_failed'));
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
                'sponsor' => $values['sponsor'],
            ];
        }

        return [
            'rows' => $parsedRows,
            'headerRowNumber' => $headerRowNumber,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows
     */
    private function detectHeaderRowNumber(array $sheetRows): ?int
    {
        foreach ($sheetRows as $index => $row) {
            $columnMap = $this->mapColumns($row);

            if ($columnMap['student_number'] !== null) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     student_number: int|null,
     *     sponsor: int|null,
     * }
     */
    private function mapColumns(array $row): array
    {
        $normalizedHeaders = [];

        foreach (array_values($row) as $index => $value) {
            $normalizedHeaders[$index] = $this->normalizeHeader((string) $value);
        }

        $columnMap = [
            'student_number' => null,
            'sponsor' => null,
        ];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '' || in_array($header, ['NO', 'NO.', 'SURNAME', 'FIRST NAME', 'DOB', 'INDICATOR'], true)) {
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
     * @param  array<string, mixed>  $row
     * @param  array{
     *     student_number: int|null,
     *     sponsor: int|null,
     * }  $columnMap
     * @return array{
     *     student_number: string|null,
     *     sponsor: string|null,
     * }
     */
    private function extractRowValues(array $row, array $columnMap): array
    {
        $values = array_values($row);

        return [
            'student_number' => $this->cellValue($values, $columnMap['student_number']),
            'sponsor' => $this->cellValue($values, $columnMap['sponsor']),
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
     * @param  array{
     *     student_number: string|null,
     *     sponsor: string|null,
     * }  $values
     */
    private function isBlankRow(array $values): bool
    {
        return $values['student_number'] === null
            && $values['sponsor'] === null;
    }

    /**
     * @param  array{
     *     student_number: string|null,
     *     sponsor: string|null,
     * }  $values
     */
    private function isSecondaryHeaderRow(array $values): bool
    {
        $studentNumber = strtoupper((string) $values['student_number']);
        $sponsor = strtoupper((string) $values['sponsor']);

        return in_array($studentNumber, ['STUDENT NUMBER'], true)
            || in_array($sponsor, ['SPONSOR', 'SPONSOR NAME'], true);
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['_', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
