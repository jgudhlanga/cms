<?php

declare(strict_types=1);

namespace App\Services\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\DisabilityStatusEnum;
use App\Importers\HMS\HostelOccupantImporter;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\HMS\HostelRoomSection;
use App\Models\Shared\NextOfKin;
use App\Models\Students\Student;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\ReturningStudentContextService;
use App\Support\HMS\HostelApplicationPaymentVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class HostelOccupantImportService
{
    public function __construct(
        private readonly HostelOccupantImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly HostelAccommodationPaymentMatcher $paymentMatcher,
        private readonly ReturningStudentContextService $returningStudentContextService,
        private readonly HostelRoomAvailabilityService $roomAvailabilityService,
        private readonly HostelRoomSectionService $roomSectionService,
        private readonly HostelStudentAllocationService $allocationService,
        private readonly HostelApplicationSemesterService $semesterService,
        private readonly HostelApplicationWindowService $windowService,
    ) {}

    /**
     * @return array{
     *     summary: array{total: int, ready: int, assumedPaid: int, errors: int, selectable: int},
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(UploadedFile $file, Hostel $hostel): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $storedPath = $file->storeAs(
            'hostel-occupant-imports/previews',
            Str::uuid()->toString().($extension !== '' ? '.'.$extension : ''),
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
            $rows[] = $this->buildPreviewRow($parsedRow, $hostel);
        }

        $this->applySectionCollisions($rows);

        return [
            'summary' => $this->summaryFromRows($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array{
     *     rowNumber: int,
     *     studentId: int,
     *     disability?: string|null,
     *     hostelRoomId: int,
     *     hostelRoomSectionId: int,
     * }>  $rows
     * @return array{
     *     summary: array{requested: int, imported: int, skipped: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(array $rows, Hostel $hostel): array
    {
        $results = [];
        $imported = 0;
        $skipped = 0;
        $claimedSectionIds = [];

        foreach ($rows as $row) {
            $rowNumber = (int) $row['rowNumber'];
            $sectionId = (int) $row['hostelRoomSectionId'];

            if ($sectionId > 0 && in_array($sectionId, $claimedSectionIds, true)) {
                $results[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'reason' => __('hms.import_occupants_section_collision'),
                ];
                $skipped++;

                continue;
            }

            $outcome = $this->processRow($row, $hostel);

            $results[] = $outcome;

            if ($outcome['status'] === 'imported') {
                $imported++;

                if ($sectionId > 0) {
                    $claimedSectionIds[] = $sectionId;
                }
            } else {
                $skipped++;
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

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     idNumber: string|null,
     *     passportNumber: string|null,
     *     disability: string|null,
     *     hostel: string|null,
     *     floor: string|null,
     *     room: string|null,
     *     section: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    public function buildPreviewRow(array $parsedRow, Hostel $hostel): array
    {
        $base = $this->emptyRow($parsedRow);

        if ($parsedRow['studentNumber'] === null && $parsedRow['idNumber'] === null && $parsedRow['passportNumber'] === null) {
            return $this->invalidRow($base, [__('hms.import_occupants_missing_identifier')]);
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
            return $this->invalidRow($base, [__('hms.import_occupants_student_not_found')]);
        }

        $student->loadMissing(['user', 'gender', 'latestEnrolment', 'nextOfKins.contacts']);

        $base['status'] = 'ready';
        $base['studentId'] = (int) $student->id;
        $base['studentName'] = $student->user?->full_name;
        $base['matchedBy'] = $resolved['matchedBy'];
        $base['storedStudentNumber'] = $student->student_number;
        $base['storedIdNumber'] = $student->id_number;
        $base['storedPassportNumber'] = $student->passport_number;

        $hostelError = $this->hostelNameError($parsedRow['hostel'], $hostel);

        if ($hostelError !== null) {
            return $this->invalidRow($base, [$hostelError]);
        }

        $skipReasons = [];
        $warnings = [];

        if ($this->hasOpenApplication((int) $student->id)) {
            $skipReasons[] = __('hms.import_occupants_pending_application');
        }

        if ($this->allocationService->studentHasOpenAllocation((int) $student->id)) {
            $skipReasons[] = __('hms.import_occupants_already_allocated');
        }

        if ($this->genderMismatchesHostel($student, $hostel)) {
            $skipReasons[] = __('hms.import_occupants_gender_mismatch');
        }

        $placement = $this->resolvePlacement($parsedRow, $hostel);
        $base['hostelRoomId'] = $placement['hostelRoomId'];
        $base['hostelRoomSectionId'] = $placement['hostelRoomSectionId'];
        $base['resolvedFloor'] = $placement['floorNumber'];
        $base['resolvedRoom'] = $placement['roomName'];
        $base['resolvedSection'] = $placement['sectionName'];

        if ($placement['error'] !== null) {
            $skipReasons[] = $placement['error'];
        }

        $disability = $this->parseDisability($parsedRow['disability']);
        $base['disability'] = $disability['value'];

        if ($disability['value'] === DisabilityStatusEnum::YES->value
            && $placement['floorNumber'] !== null
            && $placement['floorNumber'] !== 0) {
            $warnings[] = __('hms.import_occupants_disability_ground_warning');
        }

        $nextOfKin = $this->resolveNextOfKin($student);
        $base['nextOfKinName'] = $nextOfKin['name'];
        $base['nextOfKinContact'] = $nextOfKin['contact'];

        if ($nextOfKin['usedFallback']) {
            $warnings[] = __('hms.import_occupants_missing_next_of_kin');
        }

        $paymentSource = $this->resolvePaymentSource($student);
        $base['paymentSource'] = $paymentSource;
        $base['isSponsored'] = $paymentSource === 'sponsored';
        $base['isApprentice'] = $paymentSource === 'apprentice';

        if ($paymentSource === 'assumed_paid') {
            $warnings[] = __('hms.import_occupants_no_payment_evidence');
        }

        $base['warnings'] = $warnings;
        $base['skipReasons'] = $skipReasons;
        $base['errors'] = $skipReasons;
        $base['isSelectable'] = $skipReasons === [];

        return $base;
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentId: int,
     *     disability?: string|null,
     *     hostelRoomId: int,
     *     hostelRoomSectionId: int,
     * }  $row
     * @return array{rowNumber: int, status: string, reason?: string}
     */
    private function processRow(array $row, Hostel $hostel): array
    {
        $rowNumber = (int) $row['rowNumber'];
        $student = Student::query()
            ->with(['user', 'gender', 'latestEnrolment', 'nextOfKins.contacts'])
            ->find((int) $row['studentId']);

        if (! $student instanceof Student) {
            return $this->skipped($rowNumber, __('hms.import_occupants_student_not_found'));
        }

        if ($this->hasOpenApplication((int) $student->id)) {
            return $this->skipped($rowNumber, __('hms.import_occupants_pending_application'));
        }

        if ($this->allocationService->studentHasOpenAllocation((int) $student->id)) {
            return $this->skipped($rowNumber, __('hms.import_occupants_already_allocated'));
        }

        if ($this->genderMismatchesHostel($student, $hostel)) {
            return $this->skipped($rowNumber, __('hms.import_occupants_gender_mismatch'));
        }

        $room = HostelRoom::query()
            ->whereKey((int) $row['hostelRoomId'])
            ->where('hostel_id', $hostel->id)
            ->where('status', '!=', 'maintenance')
            ->first();

        if (! $room instanceof HostelRoom) {
            return $this->skipped($rowNumber, __('hms.import_occupants_room_not_found'));
        }

        $this->roomSectionService->ensureSectionsForRoom($room);

        $section = HostelRoomSection::query()
            ->whereKey((int) $row['hostelRoomSectionId'])
            ->where('hostel_room_id', $room->id)
            ->first();

        if (! $section instanceof HostelRoomSection) {
            return $this->skipped($rowNumber, __('hms.import_occupants_section_not_found'));
        }

        if ($this->sectionIsOccupied((int) $section->id)) {
            return $this->skipped($rowNumber, __('hms.import_occupants_section_occupied'));
        }

        try {
            DB::transaction(function () use ($student, $hostel, $room, $section, $row): void {
                $this->importOccupant($student, $hostel, $room, $section, $row['disability'] ?? null);
            });
        } catch (Throwable) {
            return $this->skipped($rowNumber, __('hms.import_occupants_row_failed'));
        }

        return [
            'rowNumber' => $rowNumber,
            'status' => 'imported',
        ];
    }

    private function importOccupant(
        Student $student,
        Hostel $hostel,
        HostelRoom $room,
        HostelRoomSection $section,
        mixed $disability,
    ): void {
        $parsedDisability = $this->parseDisability(is_string($disability) ? $disability : null);

        if ($parsedDisability['value'] !== null) {
            $student->update(['disability_status' => $parsedDisability['value']]);
        }

        $dates = $this->resolveStayDates($student, (int) $hostel->tenant_id);
        $nextOfKin = $this->resolveNextOfKin($student);
        $verification = HostelApplicationPaymentVerification::defaults();
        $verification[HostelApplicationPaymentVerification::KEY_ACCOMMODATION_FEES_PAID] = true;

        $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
            'tenant_id' => $hostel->tenant_id,
            'student_id' => $student->id,
            'student_enrolment_id' => $student->latestEnrolment?->id,
            'name' => $student->user?->full_name,
            'gender_id' => $student->gender_id,
            'type' => HostelApplicationTypeEnum::STUDENT,
            'status' => HostelApplicationStatusEnum::APPROVED,
            'phone_number' => $student->user?->phone_number,
            'email_address' => $student->user?->email,
            'next_of_kin_name' => $nextOfKin['name'],
            'next_of_kin_contact' => $nextOfKin['contact'],
            'check_in' => $dates['checkIn'],
            'check_out' => $dates['checkOut'],
            'payment_due_at' => null,
            'payment_verification' => $verification,
            'address_outside_campus_priority' => false,
        ]));

        HostelRoomAllocation::query()->create([
            'tenant_id' => $hostel->tenant_id,
            'hostel_room_id' => $room->id,
            'hostel_room_section_id' => $section->id,
            'student_id' => $student->id,
            'type' => HostelAllocationTypeEnum::DIRECT,
            'status' => HostelAllocationStatusEnum::ACTIVE,
            'check_in' => $application->check_in,
            'check_out' => $application->check_out,
        ]);

        $room->syncOccupancyFromAllocations();
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
                    'error' => __('hms.import_occupants_zimbabwean_passport'),
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
                'error' => __('hms.import_occupants_identity_mismatch'),
            ];
        }

        if ($byNumber instanceof Student) {
            if ($idNumber !== null && ! $this->nationalIdsMatch($idNumber, $byNumber->id_number)) {
                return [
                    'student' => null,
                    'matchedBy' => null,
                    'error' => __('hms.import_occupants_identity_mismatch'),
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
                    'error' => __('hms.import_occupants_identity_mismatch'),
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
            'error' => __('hms.import_occupants_student_not_found'),
        ];
    }

    /**
     * @param  array{
     *     hostel: string|null,
     *     floor: string|null,
     *     room: string|null,
     *     section: string|null,
     * }  $parsedRow
     * @return array{
     *     hostelRoomId: int|null,
     *     hostelRoomSectionId: int|null,
     *     floorNumber: int|null,
     *     roomName: string|null,
     *     sectionName: string|null,
     *     error: string|null,
     * }
     */
    private function resolvePlacement(array $parsedRow, Hostel $hostel): array
    {
        $empty = [
            'hostelRoomId' => null,
            'hostelRoomSectionId' => null,
            'floorNumber' => $this->parseFloor($parsedRow['floor']),
            'roomName' => $parsedRow['room'],
            'sectionName' => $parsedRow['section'] !== null ? strtoupper(trim($parsedRow['section'])) : null,
            'error' => null,
        ];

        $floorNumber = $empty['floorNumber'];
        $roomName = $parsedRow['room'];
        $sectionName = $empty['sectionName'];

        if ($floorNumber === null) {
            $empty['error'] = __('hms.import_occupants_floor_not_found');

            return $empty;
        }

        if (! $this->hostelHasFloor($hostel, $floorNumber)) {
            $empty['error'] = __('hms.import_occupants_floor_not_found');

            return $empty;
        }

        if ($roomName === null) {
            $empty['error'] = __('hms.import_occupants_room_not_found');

            return $empty;
        }

        $room = HostelRoom::query()
            ->where('hostel_id', $hostel->id)
            ->where('floor_number', $floorNumber)
            ->where('status', '!=', 'maintenance')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($roomName))])
            ->first();

        if (! $room instanceof HostelRoom) {
            $empty['error'] = __('hms.import_occupants_room_not_found');

            return $empty;
        }

        $this->roomSectionService->ensureSectionsForRoom($room);

        if ($sectionName === null || ! in_array($sectionName, ['A', 'B', 'C'], true)) {
            $empty['error'] = __('hms.import_occupants_section_not_found');
            $empty['hostelRoomId'] = (int) $room->id;
            $empty['roomName'] = $room->name;

            return $empty;
        }

        $section = HostelRoomSection::query()
            ->where('hostel_room_id', $room->id)
            ->whereRaw('UPPER(TRIM(name)) = ?', [$sectionName])
            ->first();

        if (! $section instanceof HostelRoomSection) {
            $empty['error'] = __('hms.import_occupants_section_not_found');
            $empty['hostelRoomId'] = (int) $room->id;
            $empty['roomName'] = $room->name;

            return $empty;
        }

        $empty['hostelRoomId'] = (int) $room->id;
        $empty['hostelRoomSectionId'] = (int) $section->id;
        $empty['roomName'] = $room->name;
        $empty['sectionName'] = $section->name;

        if ($this->sectionIsOccupied((int) $section->id)) {
            $empty['error'] = __('hms.import_occupants_section_occupied');
        }

        return $empty;
    }

    private function hostelNameError(?string $value, Hostel $hostel): ?string
    {
        if ($value === null || trim($value) === '') {
            return __('hms.import_occupants_hostel_not_found');
        }

        if ($this->hostelMatches($value, $hostel)) {
            return null;
        }

        $exists = Hostel::query()
            ->where('tenant_id', $hostel->tenant_id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($value))])
            ->exists();

        return $exists
            ? __('hms.import_occupants_wrong_hostel')
            : __('hms.import_occupants_hostel_not_found');
    }

    private function hostelMatches(?string $value, Hostel $hostel): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        $normalized = strtolower(trim($value));

        return $normalized === strtolower(trim((string) $hostel->name))
            || $normalized === (string) $hostel->id;
    }

    private function hostelHasFloor(Hostel $hostel, int $floorNumber): bool
    {
        $floorCount = (int) ($hostel->floor_count ?? 0);

        if ($floorCount > 0 && $floorNumber >= 0 && $floorNumber < $floorCount) {
            return true;
        }

        return HostelRoom::query()
            ->where('hostel_id', $hostel->id)
            ->where('floor_number', $floorNumber)
            ->exists();
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

    private function genderMismatchesHostel(Student $student, Hostel $hostel): bool
    {
        $hostelType = $hostel->type;

        if ($hostelType === null || $hostelType === 'mixed') {
            return false;
        }

        $expected = $this->roomAvailabilityService->hostelTypeForGender($student->gender_id);

        if ($expected === null) {
            return true;
        }

        return $expected !== $hostelType;
    }

    private function hasOpenApplication(int $studentId): bool
    {
        return HostelApplication::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [
                HostelApplicationStatusEnum::PENDING->value,
                HostelApplicationStatusEnum::AWAITING_PAYMENT->value,
                HostelApplicationStatusEnum::PARTIALLY_PAID->value,
                HostelApplicationStatusEnum::PAID->value,
                HostelApplicationStatusEnum::APPROVED->value,
            ])
            ->exists();
    }

    private function sectionIsOccupied(int $sectionId): bool
    {
        return HostelRoomAllocation::query()
            ->active()
            ->where('hostel_room_section_id', $sectionId)
            ->exists();
    }

    private function parseFloor(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace(['floor', 'flr'], '', $normalized);
        $normalized = trim($normalized);

        if (in_array($normalized, ['0', 'g', 'grd', 'ground'], true)) {
            return 0;
        }

        if (is_numeric($normalized)) {
            return (int) $normalized;
        }

        return null;
    }

    /**
     * @return array{value: string|null}
     */
    private function parseDisability(?string $value): array
    {
        if ($value === null) {
            return ['value' => null];
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'yes', 'y', 'true', '1' => ['value' => DisabilityStatusEnum::YES->value],
            'no', 'n', 'false', '0' => ['value' => DisabilityStatusEnum::NO->value],
            default => ['value' => null],
        };
    }

    /**
     * @return array{name: string, contact: string, usedFallback: bool}
     */
    private function resolveNextOfKin(Student $student): array
    {
        $kin = $student->nextOfKins->first();

        if ($kin instanceof NextOfKin && filled($kin->name)) {
            $contact = $kin->contacts->first()?->phone_number
                ?? $kin->contacts->first()?->email_address
                ?? $student->user?->phone_number
                ?? $student->user?->email
                ?? 'N/A';

            return [
                'name' => (string) $kin->name,
                'contact' => (string) $contact,
                'usedFallback' => false,
            ];
        }

        return [
            'name' => (string) ($student->user?->full_name ?: 'Next of kin'),
            'contact' => (string) ($student->user?->phone_number ?: $student->user?->email ?: 'N/A'),
            'usedFallback' => true,
        ];
    }

    private function resolvePaymentSource(Student $student): string
    {
        $student->loadMissing(['apprentices', 'studentSponsors']);
        if ($this->returningStudentContextService->currentSponsorForStudentProfile($student) !== null) {
            return 'sponsored';
        }

        if ($this->returningStudentContextService->currentApprenticeForStudentProfile($student) !== null) {
            return 'apprentice';
        }

        return $this->paymentMatcher->evidenceSource($student) ?? 'assumed_paid';
    }

    /**
     * @return array{checkIn: string, checkOut: string}
     */
    private function resolveStayDates(Student $student, int $tenantId): array
    {
        $semesterDates = $this->semesterService->datesForApplication($student);

        if ($semesterDates['success'] && $semesterDates['checkIn'] !== null && $semesterDates['checkOut'] !== null) {
            return [
                'checkIn' => $semesterDates['checkIn'],
                'checkOut' => $semesterDates['checkOut'],
            ];
        }

        $windowDates = $this->windowService->configuredApplicationDates($tenantId);

        if ($windowDates['checkIn'] !== null && $windowDates['checkOut'] !== null) {
            return [
                'checkIn' => $windowDates['checkIn'],
                'checkOut' => $windowDates['checkOut'],
            ];
        }

        return [
            'checkIn' => now()->toDateString(),
            'checkOut' => now()->addMonths(4)->toDateString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function applySectionCollisions(array &$rows): void
    {
        $firstRowBySection = [];

        foreach ($rows as $index => $row) {
            $sectionId = $row['hostelRoomSectionId'] ?? null;

            if (! is_int($sectionId) && ! is_numeric($sectionId)) {
                continue;
            }

            $sectionId = (int) $sectionId;

            if ($sectionId < 1 || ! ($row['isSelectable'] ?? false)) {
                continue;
            }

            if (! array_key_exists($sectionId, $firstRowBySection)) {
                $firstRowBySection[$sectionId] = $index;

                continue;
            }

            $rows[$index]['skipReasons'][] = __('hms.import_occupants_section_collision');
            $rows[$index]['errors'] = $rows[$index]['skipReasons'];
            $rows[$index]['isSelectable'] = false;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{total: int, ready: int, assumedPaid: int, errors: int, selectable: int}
     */
    private function summaryFromRows(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'ready' => 0,
            'assumedPaid' => 0,
            'errors' => 0,
            'selectable' => 0,
        ];

        foreach ($rows as $row) {
            if ($row['isSelectable']) {
                $summary['ready']++;
                $summary['selectable']++;
            } else {
                $summary['errors']++;
            }

            if (($row['paymentSource'] ?? null) === 'assumed_paid' && $row['isSelectable']) {
                $summary['assumedPaid']++;
            }
        }

        return $summary;
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     idNumber: string|null,
     *     passportNumber: string|null,
     *     disability: string|null,
     *     hostel: string|null,
     *     floor: string|null,
     *     room: string|null,
     *     section: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function emptyRow(array $parsedRow): array
    {
        return [
            'rowNumber' => $parsedRow['rowNumber'],
            'studentNumber' => $parsedRow['studentNumber'],
            'idNumber' => $parsedRow['idNumber'],
            'passportNumber' => $parsedRow['passportNumber'] ?? null,
            'disability' => $parsedRow['disability'],
            'hostel' => $parsedRow['hostel'],
            'floor' => $parsedRow['floor'],
            'room' => $parsedRow['room'],
            'section' => $parsedRow['section'],
            'status' => 'invalid',
            'studentId' => null,
            'studentName' => null,
            'matchedBy' => null,
            'storedStudentNumber' => null,
            'storedIdNumber' => null,
            'storedPassportNumber' => null,
            'hostelRoomId' => null,
            'hostelRoomSectionId' => null,
            'resolvedFloor' => null,
            'resolvedRoom' => null,
            'resolvedSection' => null,
            'nextOfKinName' => null,
            'nextOfKinContact' => null,
            'paymentSource' => null,
            'isSponsored' => false,
            'isApprentice' => false,
            'errors' => [],
            'warnings' => [],
            'skipReasons' => [],
            'isSelectable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function invalidRow(array $row, array $errors): array
    {
        $row['status'] = 'invalid';
        $row['errors'] = $errors;
        $row['skipReasons'] = $errors;
        $row['isSelectable'] = false;

        return $row;
    }

    /**
     * @return array{rowNumber: int, status: string, reason: string}
     */
    private function skipped(int $rowNumber, string $reason): array
    {
        return [
            'rowNumber' => $rowNumber,
            'status' => 'skipped',
            'reason' => $reason,
        ];
    }
}
