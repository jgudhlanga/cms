<?php

namespace App\Exports\Enrolments;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BulkFinaliseDryRunExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $successes
     * @param  array<int, array{student_program_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $failures
     */
    public function __construct(
        private readonly array $successes,
        private readonly array $failures,
    ) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new BulkFinaliseSuccessesExport($this->successes),
            new BulkFinaliseFailuresExport($this->failures),
        ];
    }
}
