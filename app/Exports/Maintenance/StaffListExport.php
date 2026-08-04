<?php

declare(strict_types=1);

namespace App\Exports\Maintenance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;

class StaffListExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder
{
    /**
     * @param  list<list<string|null>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    /**
     * @return list<list<string|null>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== '' && $this->shouldForceString($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function shouldForceString(string $value): bool
    {
        if (str_starts_with($value, '+')) {
            return true;
        }

        return PhpSpreadsheetDefaultValueBinder::dataTypeForValue($value) !== DataType::TYPE_STRING;
    }
}
