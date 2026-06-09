<?php

declare(strict_types=1);

namespace App\Exceptions\Maintenance;

use Exception;

class StudentIdNumberConflictException extends Exception
{
    public function __construct(
        public readonly int $conflictingStudentId,
        public readonly string $idNumber,
    ) {
        parent::__construct(__('trans.maintenance_faulty_data_id_conflict'));
    }
}
