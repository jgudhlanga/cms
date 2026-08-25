<?php

namespace App\Exports\Assessments;

use Maatwebsite\Excel\Concerns\FromArray;

class MissingMarksReportExport implements FromArray
{
    /**
     * @param  list<list<string|int|null>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    /**
     * @return list<list<string|int|null>>
     */
    public function array(): array
    {
        return $this->rows;
    }
}
