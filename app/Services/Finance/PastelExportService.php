<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Http\Requests\Finance\ExportPastelRequest;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Shared\Address;
use App\Models\Students\StudentEnrolment;
use App\Queries\Finance\PastelExportQuery;
use App\Support\Exports\CsvExportWriter;
use App\Support\Exports\StudentExportRowMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PastelExportService
{
    public const OUTPUT_PATH = 'reports/finance/pastel-export.csv';

    private const GROUP_CODE = '2278';

    private const AREA_CODE = '2288';

    private const CURRENCY_CODE = 'USD';

    /**
     * @var list<string>
     */
    public const HEADERS = [
        'name',
        'student_number',
        'department',
        'course',
        'LEVEL',
        'DURATION',
        'mode_of_study',
        'RESIDENT',
        'ENROLLED AS',
        'EXEMPTIONS',
        'NEXT OF KIN',
        'SPONSOR',
        'ADDRESS',
        'CELL NO:',
        'Group',
        'Area',
        'Currency Code',
    ];

    public function __construct(
        protected PastelExportQuery $query,
        protected CsvExportWriter $csvExportWriter,
        protected StudentExportRowMapper $studentExportRowMapper,
    ) {}

    public function export(ExportPastelRequest $request): string
    {
        $intakePeriodId = $request->intakePeriodId();
        $workflowStepIds = $request->workflowStepIds();
        $studentNumberStartsWith = $request->studentNumberStartsWith();
        $fallbackTenantId = (int) (Auth::user()?->tenant_id ?? 0);
        /** @var array<int, array{tenant_id: int, student_id: int, student_number: ?string}> $studentsToLink */
        $studentsToLink = [];

        $relativePath = $this->csvExportWriter->write(
            self::OUTPUT_PATH,
            self::HEADERS,
            function ($handle) use ($intakePeriodId, $workflowStepIds, $studentNumberStartsWith, $fallbackTenantId, &$studentsToLink): void {
                $this->query
                    ->baseQuery($intakePeriodId, $workflowStepIds, $studentNumberStartsWith)
                    ->chunkById(200, function (Collection $enrolments) use ($handle, $fallbackTenantId, &$studentsToLink): void {
                        foreach ($enrolments as $enrolment) {
                            /** @var StudentEnrolment $enrolment */
                            fputcsv($handle, $this->mapRow($enrolment));

                            $studentId = $enrolment->student_id;

                            if ($studentId === null || isset($studentsToLink[$studentId])) {
                                continue;
                            }

                            $student = $enrolment->student;
                            $tenantId = (int) ($student?->tenant_id ?? $fallbackTenantId);

                            if ($tenantId < 1) {
                                continue;
                            }

                            $studentsToLink[$studentId] = [
                                'tenant_id' => $tenantId,
                                'student_id' => (int) $studentId,
                                'student_number' => $student?->student_number,
                            ];
                        }
                    }, column: 'id');
            },
        );

        $this->markStudentsAsLinked($studentsToLink, $intakePeriodId);

        return $relativePath;
    }

    /**
     * @param  array<int, array{tenant_id: int, student_id: int, student_number: ?string}>  $studentsToLink
     */
    private function markStudentsAsLinked(array $studentsToLink, int $intakePeriodId): void
    {
        if ($studentsToLink === []) {
            return;
        }

        $now = now();
        $linkedBy = Auth::id();

        $rows = array_map(
            static fn (array $student): array => [
                'tenant_id' => $student['tenant_id'],
                'student_id' => $student['student_id'],
                'student_number' => $student['student_number'],
                'intake_period_id' => $intakePeriodId,
                'linked_by' => $linkedBy,
                'linked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_values($studentsToLink),
        );

        DB::table((new PastelLinkedStudent)->getTable())->insertOrIgnore($rows);
    }

    /**
     * @return list<string|null>
     */
    public function mapRow(StudentEnrolment $enrolment): array
    {
        $student = $enrolment->student;
        $user = $student?->user;
        $studentApplication = $enrolment->studentApplication;
        $mainAddress = $this->studentExportRowMapper->resolveMainAddress($student);
        $nextOfKin = $student?->nextOfKins->first();
        $sponsor = $student?->sponsors->first();

        return [
            $this->resolveName($user?->last_name, $user?->first_name),
            $student?->student_number,
            $enrolment->institutionDepartment?->department?->name,
            $enrolment->departmentCourse?->course?->name,
            $enrolment->departmentLevel?->level?->name,
            null,
            $studentApplication?->modeOfStudy?->name ?? $enrolment->modeOfStudy?->name,
            $student?->activeHostelAllocation !== null ? 'Yes' : 'No',
            $student !== null && $student->apprentices->isNotEmpty() ? 'Apprentice' : 'Direct',
            null,
            $nextOfKin?->name,
            $sponsor?->name,
            $this->formatAddress($mainAddress),
            $this->studentExportRowMapper->resolvePhone($student),
            self::GROUP_CODE,
            self::AREA_CODE,
            self::CURRENCY_CODE,
        ];
    }

    private function resolveName(?string $lastName, ?string $firstName): ?string
    {
        $parts = array_filter([$lastName, $firstName]);

        if ($parts === []) {
            return null;
        }

        return strtoupper(implode(' ', $parts));
    }

    private function formatAddress(?Address $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $formatted = trim(implode(', ', array_filter([
            $address->address_1,
            $address->address_2,
            $address->address_3,
            $address->address_4,
        ])));

        return $formatted !== '' ? $formatted : null;
    }
}
