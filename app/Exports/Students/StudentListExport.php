<?php

declare(strict_types=1);

namespace App\Exports\Students;

use Maatwebsite\Excel\Concerns\FromArray;

class StudentListExport implements FromArray
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
}
