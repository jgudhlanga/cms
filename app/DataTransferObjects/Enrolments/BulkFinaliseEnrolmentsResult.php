<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Enrolments;

use Carbon\CarbonImmutable;

final readonly class BulkFinaliseEnrolmentsResult
{
    /**
     * @param  array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $successes
     * @param  array<int, array{student_application_id:int, student_id:int|null, student_number:string|null, student_id_number:string|null, user_full_name:string|null, class_list_id:int|null, reason:string, start_date:string, end_date:string, department:string|null, course:string|null, level:string|null}>  $failures
     */
    public function __construct(
        public int $successfulFinalised,
        public int $failedFinalisations,
        public array $successes,
        public array $failures,
        public ?string $reportPath,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public bool $dryRun,
        public bool $aborted = false,
        public ?string $abortMessage = null,
    ) {}
}
