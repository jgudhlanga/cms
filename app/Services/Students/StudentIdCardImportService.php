<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\IdCardRequestStatusEnum;
use App\Exceptions\Students\InvalidIdCardRequestTransitionException;
use App\Importers\Students\StudentIdCardImporter;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Users\User;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StudentIdCardImportService
{
    public function __construct(
        private readonly StudentIdCardImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly StudentIdCardPhotoService $photoService,
        private readonly StudentIdCardRequestService $requestService,
    ) {}

    /**
     * @return array{
     *     summary: array{total: int, ready: int, errors: int, selectable: int},
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $storedPath = $file->storeAs(
            'id-card-imports/previews',
            Str::uuid()->toString().'.csv',
            'ingest',
        );
        $absolutePath = Storage::disk('ingest')->path($storedPath);

        try {
            $parsed = $this->importer->parse($absolutePath);
        } finally {
            Storage::disk('ingest')->delete($storedPath);
        }

        $rows = [];

        foreach ($parsed['rows'] as $parsedRow) {
            $rows[] = $this->buildPreviewRow($parsedRow);
        }

        $this->applyStudentCollisions($rows);

        return [
            'summary' => $this->summaryFromRows($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array{rowNumber: int, studentId: int}>  $rows
     * @return array{
     *     summary: array{requested: int, imported: int, skipped: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(array $rows, User $admin): array
    {
        $results = [];
        $imported = 0;
        $skipped = 0;
        $claimedStudentIds = [];

        foreach ($rows as $row) {
            $rowNumber = (int) $row['rowNumber'];
            $studentId = (int) $row['studentId'];

            if (in_array($studentId, $claimedStudentIds, true)) {
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'reason' => __('students.id_card_import_duplicate_row'),
                ];
                $skipped++;

                continue;
            }

            $student = Student::query()->find($studentId);
            if (! $student instanceof Student) {
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'reason' => __('students.id_card_import_student_not_found'),
                ];
                $skipped++;

                continue;
            }

            try {
                $this->requestService->importApproved($student, $admin);
                $claimedStudentIds[] = $studentId;
                $imported++;
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'imported',
                ];
            } catch (InvalidIdCardRequestTransitionException $exception) {
                $skipped++;
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'reason' => $exception->getMessage(),
                ];
            } catch (Throwable) {
                $skipped++;
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'reason' => __('trans.student_id_card_import_process_failed'),
                ];
            }
        }

        return [
            'summary' => [
                'requested' => count($rows),
                'imported' => $imported,
                'skipped' => $skipped,
            ],
            'rows' => $results,
        ];
    }

    public function templateCsv(): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, StudentIdCardImporter::COLUMNS);
        fputcsv($handle, ['H123456', '63-123456A63', '']);
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    public function templateFileName(): string
    {
        return 'student-id-card-import-'.now()->format('Y-m-d-His').'.csv';
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     idNumber: string|null,
     *     passportNumber: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function buildPreviewRow(array $parsedRow): array
    {
        $base = $this->emptyRow($parsedRow);

        if ($parsedRow['studentNumber'] === null && $parsedRow['idNumber'] === null && $parsedRow['passportNumber'] === null) {
            return $this->invalidRow($base, [__('students.id_card_import_missing_identifier')]);
        }

        $resolved = $this->resolveStudent(
            $parsedRow['studentNumber'],
            $parsedRow['idNumber'],
            $parsedRow['passportNumber'],
        );

        if ($resolved['error'] !== null) {
            return $this->invalidRow($base, [$resolved['error']]);
        }

        $student = $resolved['student'];
        if (! $student instanceof Student) {
            return $this->invalidRow($base, [__('students.id_card_import_student_not_found')]);
        }

        $student->loadMissing(['user', 'latestIdCardRequest']);

        $base['studentId'] = (int) $student->id;
        $base['studentName'] = $student->user?->full_name;
        $base['matchedBy'] = $resolved['matchedBy'];
        $base['storedStudentNumber'] = $student->student_number;
        $base['storedIdNumber'] = $student->id_number;
        $base['storedPassportNumber'] = $student->passport_number;
        $base['identityType'] = $student->isZimbabwean()
            ? __('trans.student_id_card_national_id')
            : __('trans.student_id_card_passport_number');
        $base['hasPhoto'] = $this->photoService->hasPrintPhoto($student);
        $base['photoThumbUrl'] = $this->photoService->printPhotoThumbUrl($student);

        $active = $this->activeRequest($student);
        if ($active instanceof StudentIdCardRequest) {
            $base['existingRequestId'] = (int) $active->id;
            $base['existingRequestStatus'] = $active->status?->label();

            return $this->invalidRow($base, [__('students.id_card_import_active_request', [
                'status' => $active->status?->label() ?? $active->status?->value,
                'id' => $active->id,
            ])]);
        }

        $errors = [];
        $warnings = [];

        if (trim((string) $student->student_number) === '') {
            $errors[] = __('students.id_card_import_student_number_required');
        }

        if (! $base['hasPhoto']) {
            $errors[] = __('students.id_card_import_photo_required');
        }

        $issued = $student->idCardRequests()
            ->where('status', IdCardRequestStatusEnum::ISSUED)
            ->exists();

        if ($issued) {
            $warnings[] = __('students.id_card_import_previously_issued');
        }

        if ($errors !== []) {
            return $this->invalidRow($base, $errors, $warnings);
        }

        $base['status'] = 'ready';
        $base['warnings'] = $warnings;
        $base['isSelectable'] = true;

        return $base;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function applyStudentCollisions(array &$rows): void
    {
        $counts = [];

        foreach ($rows as $row) {
            $studentId = $row['studentId'] ?? null;
            if (! is_int($studentId)) {
                continue;
            }

            $counts[$studentId] = ($counts[$studentId] ?? 0) + 1;
        }

        foreach ($rows as &$row) {
            $studentId = $row['studentId'] ?? null;
            if (! is_int($studentId) || ($counts[$studentId] ?? 0) < 2) {
                continue;
            }

            $row = $this->invalidRow($row, [__('students.id_card_import_duplicate_row')], $row['warnings'] ?? []);
        }
        unset($row);
    }

    /**
     * @return array{student: Student|null, matchedBy: string|null, error: string|null}
     */
    private function resolveStudent(?string $studentNumber, ?string $idNumber, ?string $passportNumber): array
    {
        $byNumber = $studentNumber !== null
            ? $this->lookupService->findStudentByStudentNumber($studentNumber)
            : null;

        $byId = $idNumber !== null
            ? $this->lookupService->findStudentByNationalId($idNumber)
            : null;

        $byPassport = $passportNumber !== null
            ? $this->lookupService->findStudentByPassport($passportNumber)
            : null;

        if ($byPassport instanceof Student && $byPassport->isZimbabwean()) {
            if (! $byNumber instanceof Student && ! $byId instanceof Student) {
                return [
                    'student' => null,
                    'matchedBy' => null,
                    'error' => __('students.id_card_import_zimbabwean_passport'),
                ];
            }

            $byPassport = null;
        }

        $matched = array_values(array_filter(
            [$byNumber, $byId, $byPassport],
            static fn (mixed $student): bool => $student instanceof Student,
        ));

        $uniqueIds = array_unique(array_map(
            static fn (Student $student): int => (int) $student->id,
            $matched,
        ));

        if (count($uniqueIds) > 1) {
            return [
                'student' => null,
                'matchedBy' => null,
                'error' => __('students.id_card_import_identity_mismatch'),
            ];
        }

        if ($byNumber instanceof Student) {
            if ($idNumber !== null && ! $this->nationalIdsMatch($idNumber, $byNumber->id_number)) {
                return [
                    'student' => null,
                    'matchedBy' => null,
                    'error' => __('students.id_card_import_identity_mismatch'),
                ];
            }

            if (
                $passportNumber !== null
                && ! $byNumber->isZimbabwean()
                && ! $this->passportsMatch($passportNumber, $byNumber->passport_number)
            ) {
                return [
                    'student' => null,
                    'matchedBy' => null,
                    'error' => __('students.id_card_import_identity_mismatch'),
                ];
            }

            return [
                'student' => $byNumber,
                'matchedBy' => 'student_number',
                'error' => null,
            ];
        }

        if ($byId instanceof Student) {
            return [
                'student' => $byId,
                'matchedBy' => 'id_number',
                'error' => null,
            ];
        }

        if ($byPassport instanceof Student) {
            return [
                'student' => $byPassport,
                'matchedBy' => 'passport_number',
                'error' => null,
            ];
        }

        return [
            'student' => null,
            'matchedBy' => null,
            'error' => __('students.id_card_import_student_not_found'),
        ];
    }

    private function nationalIdsMatch(string $provided, ?string $stored): bool
    {
        if ($stored === null || trim($stored) === '') {
            return false;
        }

        $providedNormalized = EnrollmentLookupService::normalizeNationalId($provided);
        $storedNormalized = EnrollmentLookupService::normalizeNationalId($stored);

        return $providedNormalized === $storedNormalized
            || str_replace('-', '', $providedNormalized) === str_replace('-', '', $storedNormalized);
    }

    private function passportsMatch(string $provided, ?string $stored): bool
    {
        if ($stored === null || trim($stored) === '') {
            return false;
        }

        return EnrollmentLookupService::normalizePassportNumber($provided)
            === EnrollmentLookupService::normalizePassportNumber($stored);
    }

    private function activeRequest(Student $student): ?StudentIdCardRequest
    {
        return $student->idCardRequests()
            ->whereIn('status', array_map(
                static fn (IdCardRequestStatusEnum $status): string => $status->value,
                IdCardRequestStatusEnum::activeStatuses(),
            ))
            ->latest()
            ->first();
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     idNumber: string|null,
     *     passportNumber: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function emptyRow(array $parsedRow): array
    {
        return [
            'rowNumber' => $parsedRow['rowNumber'],
            'studentNumber' => $parsedRow['studentNumber'],
            'idNumber' => $parsedRow['idNumber'],
            'passportNumber' => $parsedRow['passportNumber'],
            'status' => 'invalid',
            'studentId' => null,
            'studentName' => null,
            'matchedBy' => null,
            'storedStudentNumber' => null,
            'storedIdNumber' => null,
            'storedPassportNumber' => null,
            'identityType' => null,
            'hasPhoto' => false,
            'photoThumbUrl' => null,
            'existingRequestId' => null,
            'existingRequestStatus' => null,
            'errors' => [],
            'warnings' => [],
            'skipReasons' => [],
            'isSelectable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function invalidRow(array $row, array $errors, array $warnings = []): array
    {
        $row['status'] = 'invalid';
        $row['errors'] = array_values(array_unique([...($row['errors'] ?? []), ...$errors]));
        $row['warnings'] = array_values(array_unique([...($row['warnings'] ?? []), ...$warnings]));
        $row['skipReasons'] = $row['errors'];
        $row['isSelectable'] = false;

        return $row;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{total: int, ready: int, errors: int, selectable: int}
     */
    private function summaryFromRows(array $rows): array
    {
        $ready = 0;
        $errors = 0;

        foreach ($rows as $row) {
            if ($row['isSelectable'] ?? false) {
                $ready++;
            } else {
                $errors++;
            }
        }

        return [
            'total' => count($rows),
            'ready' => $ready,
            'errors' => $errors,
            'selectable' => $ready,
        ];
    }
}
