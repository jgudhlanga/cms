<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\PermissionHelper;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Shared\IdType;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentSponsor;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\HMS\HostelOccupantImportTemplateService;
use App\Services\HMS\HostelRoomSectionService;
use Illuminate\Http\UploadedFile;

function actingAsHostelOccupantImporter(array $permissions = [
    'viewAny:hostels',
    'view:hostels',
    'import:hostel-applications',
    'create:hostel-applications',
    'confirm:hostel-payments',
]): User
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo($permissions);

    test()->actingAs($user);

    return $user;
}

/**
 * @param  list<list<string|null>>  $rows
 */
function storeHostelOccupantImportFile(array $rows): UploadedFile
{
    $relativePath = 'test-hostel-occupant-import-'.uniqid().'.csv';
    $fullPath = storage_path('app/'.$relativePath);
    $handle = fopen($fullPath, 'w');
    fputcsv($handle, [
        'Student Number',
        'ID Number',
        'Passport Number',
        'Disability',
        'Hostel',
        'Floor',
        'Room',
        'Section',
    ]);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return new UploadedFile($fullPath, 'occupants.csv', 'text/csv', null, true);
}

function hostelOccupantImportRoom(string $hostelName = 'Dean House', string $roomName = 'DH-01', string $type = 'male'): array
{
    $room = createHostelRoomForAllocationIndexTest([
        'hostel' => [
            'name' => $hostelName.' '.uniqid(),
            'type' => $type,
        ],
        'room' => [
            'name' => $roomName,
            'floor_number' => 0,
            'room_type' => 'double',
            'max_occupancy' => 2,
        ],
    ]);

    app(HostelRoomSectionService::class)->ensureSectionsForRoom($room);
    $room->load('sections');

    return [
        'room' => $room,
        'hostel' => $room->hostel,
        'sectionA' => $room->sections->firstWhere('name', 'A'),
        'sectionB' => $room->sections->firstWhere('name', 'B'),
    ];
}

function hostelOccupantImportStudent(string $studentNumber): array
{
    $student = createStudentForAllocationIndexTest();
    $student->update(['student_number' => $studentNumber]);

    return [
        'student' => $student->fresh(['user', 'gender']),
        'gender' => $student->gender,
    ];
}

/**
 * @param  array{hostel: Hostel, room: HostelRoom}  $context
 * @return list<string|null>
 */
function hostelOccupantImportCsvRow(
    Student $student,
    array $context,
    string $section = 'A',
    string $disability = 'No',
    ?string $floor = '0',
    ?string $hostelName = null,
    ?string $idNumber = null,
    ?string $passportNumber = null,
    ?string $studentNumber = null,
    ?string $roomName = null,
): array {
    return [
        $studentNumber ?? $student->student_number,
        $idNumber === null ? $student->id_number : $idNumber,
        $passportNumber,
        $disability,
        $hostelName ?? $context['hostel']->name,
        $floor,
        $roomName ?? $context['room']->name,
        $section,
    ];
}

function ensureHostelOccupantImportIdType(IdTypeEnum $idType): IdType
{
    $existing = IdType::query()->find($idType->id());

    if ($existing instanceof IdType) {
        return $existing;
    }

    return IdType::query()->forceCreate([
        'id' => $idType->id(),
        'name' => $idType->value,
        'description' => $idType->description(),
    ]);
}

it('redirects guests from the occupant import page', function (): void {
    $context = hostelOccupantImportRoom();

    $this->get(route('hostels.occupants.import', $context['hostel']))
        ->assertRedirect('/login');
});

it('forbids users without import permission from the occupant import page', function (): void {
    $context = hostelOccupantImportRoom();
    actingAsHostelOccupantImporter(['viewAny:hostels', 'view:hostels']);

    $this->get(route('hostels.occupants.import', $context['hostel']))
        ->assertForbidden();
});

it('forbids wardens from occupant import preview', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-WARDEN')['student'];
    actingAsHostelOccupantImporter(PermissionHelper::wardenPermissions());

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertForbidden();
});

it('renders the occupant import page for users with import permission', function (): void {
    $context = hostelOccupantImportRoom();
    actingAsHostelOccupantImporter();

    $this->get(route('hostels.occupants.import', $context['hostel']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('hms/hostels/occupants/Import')
            ->where('hostel.id', $context['hostel']->id)
            ->where('canConfirmPayments', true));
});

it('downloads an occupant import template', function (): void {
    $context = hostelOccupantImportRoom();
    actingAsHostelOccupantImporter();

    $response = $this->get(route('hostels.occupants.import.template', $context['hostel']));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('hostel-occupant-import');
});

it('previews a matching occupant row as ready and assumed paid', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-001')['student'];
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $response = $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.paymentSource', 'assumed_paid')
        ->assertJsonPath('rows.0.studentId', $student->id);
});

it('marks identity mismatches as invalid', function (): void {
    $context = hostelOccupantImportRoom();
    $first = hostelOccupantImportStudent('HMS-IMP-002')['student'];
    $second = hostelOccupantImportStudent('HMS-IMP-003')['student'];
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $first->student_number,
        $second->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('summary.errors', 1);
});

it('skips rows for a different hostel', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-004')['student'];
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        'Other Hostel',
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false);
});

it('skips occupied sections', function (): void {
    $context = hostelOccupantImportRoom();
    $occupant = hostelOccupantImportStudent('HMS-IMP-005')['student'];
    $incoming = hostelOccupantImportStudent('HMS-IMP-006')['student'];

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $context['room']->id,
        'hostel_room_section_id' => $context['sectionA']->id,
        'student_id' => $occupant->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]);

    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $incoming->student_number,
        $incoming->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false);
});

it('skips gender mismatches', function (): void {
    $context = hostelOccupantImportRoom('Female House', 'FH-01', 'female');
    $student = hostelOccupantImportStudent('HMS-IMP-007')['student'];
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false);
});

it('shows bank accommodation evidence in preview', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('HMS-IMP-BANK');
    $student = $studentApplication->student;
    $context = hostelOccupantImportRoom();

    ZBBankStatement::query()->create([
        'tran_number_asc' => 'T-ASC-HMS-BANK',
        'tran_number_desc' => 'T-DESC-HMS-BANK',
        'transaction_id' => 'TXN-HMS-BANK',
        'transaction_sr_id' => 'TSR-HMS-BANK',
        'transaction_date' => now()->toDateTimeString(),
        'debit_credit_flag' => 'C',
        'narration' => 'ACCOMMODATION '.$student->student_number,
    ]);

    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'Yes',
        $context['hostel']->name,
        'Ground',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.paymentSource', 'bank')
        ->assertJsonPath('rows.0.isSelectable', true);
});

it('shows ledger accommodation evidence in preview', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('HMS-IMP-LEDGER');
    $student = $studentApplication->student;
    $context = hostelOccupantImportRoom();
    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->name(),
            'description' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->description(),
            'position' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->position(),
        ],
    );

    Ledger::query()->create([
        'tenant_id' => $student->tenant_id,
        'ledgerable_type' => $student->user->getMorphClass(),
        'ledgerable_id' => $student->user->id,
        'fee_type_id' => $feeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 150,
        'system_reference' => 'ACC-LEDGER-1',
        'payment_date' => now(),
    ]);

    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'B',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.paymentSource', 'ledger');
});

it('shows sponsored students as exempt', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('HMS-IMP-SPON');
    $student = $studentApplication->student;
    $context = hostelOccupantImportRoom();

    StudentSponsor::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'calendar_year' => 2025,
        'sponsor' => 'Ministry',
    ]);

    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.paymentSource', 'sponsored');
});

it('shows apprentice students as exempt', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('HMS-IMP-APP');
    $student = $studentApplication->student;
    $context = hostelOccupantImportRoom();

    StudentApprentice::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'calendar_year' => 2025,
        'employer' => 'ZESA',
        'apprentice_number' => 'APR-1',
    ]);

    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.paymentSource', 'apprentice');
});

it('imports occupants as approved with payment confirmed', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-OK')['student'];
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'Yes',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $preview = $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()->json('rows.0');

    $this->post(route('hostels.occupants.import.process', $context['hostel']), [
        'rows' => [[
            'rowNumber' => $preview['rowNumber'],
            'studentId' => $preview['studentId'],
            'disability' => $preview['disability'],
            'hostelRoomId' => $preview['hostelRoomId'],
            'hostelRoomSectionId' => $preview['hostelRoomSectionId'],
        ]],
    ])->assertSuccessful()
        ->assertJsonPath('summary.imported', 1);

    $application = HostelApplication::query()->where('student_id', $student->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe(HostelApplicationStatusEnum::APPROVED)
        ->and($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();

    $this->assertDatabaseHas('hostel_room_allocations', [
        'student_id' => $student->id,
        'hostel_room_id' => $context['room']->id,
        'hostel_room_section_id' => $context['sectionA']->id,
        'status' => HostelAllocationStatusEnum::ACTIVE->value,
    ]);

    expect($student->fresh()->disability_status)->toBe('yes');
});

it('allows preview without confirm permission but forbids process', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-PERM')['student'];
    actingAsHostelOccupantImporter([
        'viewAny:hostels',
        'view:hostels',
        'import:hostel-applications',
        'create:hostel-applications',
    ]);

    $file = storeHostelOccupantImportFile([[
        $student->student_number,
        $student->id_number,
        null,
        'No',
        $context['hostel']->name,
        '0',
        $context['room']->name,
        'A',
    ]]);

    $preview = $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()->json('rows.0');

    $this->post(route('hostels.occupants.import.process', $context['hostel']), [
        'rows' => [[
            'rowNumber' => $preview['rowNumber'],
            'studentId' => $preview['studentId'],
            'disability' => $preview['disability'],
            'hostelRoomId' => $preview['hostelRoomId'],
            'hostelRoomSectionId' => $preview['hostelRoomSectionId'],
        ]],
    ])->assertForbidden();
});

it('assembles occupant import template columns', function (): void {
    $context = hostelOccupantImportRoom();
    $service = app(HostelOccupantImportTemplateService::class);
    $data = $service->assemble($context['hostel']);

    expect($service->columns())->toBe([
        'Student Number',
        'ID Number',
        'Passport Number',
        'Disability',
        'Hostel',
        'Floor',
        'Room',
        'Section',
    ])->and($data['header']['hostelName'])->toBe($context['hostel']->name);
});

it('rejects unknown hostels, floors, rooms, and sections', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-PLACE')['student'];
    actingAsHostelOccupantImporter();

    $unknownHostel = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, hostelName: 'No Such Hostel'),
    ]);
    $unknownFloor = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, floor: '9'),
    ]);
    $unknownRoom = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, roomName: 'ZZ-99'),
    ]);
    $unknownSection = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, section: 'Z'),
    ]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $unknownHostel,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.errors.0', __('hms.import_occupants_hostel_not_found'));

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $unknownFloor,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.errors.0', __('hms.import_occupants_floor_not_found'));

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $unknownRoom,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.errors.0', __('hms.import_occupants_room_not_found'));

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $unknownSection,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.errors.0', __('hms.import_occupants_section_not_found'));
});

it('matches non-zimbabwean students by passport number', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-PASS')['student'];
    ensureHostelOccupantImportIdType(IdTypeEnum::FOREIGN_PASSPORT_NUMBER);
    $student->update([
        'id_type_id' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id(),
        'id_number' => null,
        'passport_number' => 'AB1234567',
    ]);
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, idNumber: '', passportNumber: 'AB1234567', studentNumber: ''),
    ]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.matchedBy', 'passport_number')
        ->assertJsonPath('rows.0.studentId', $student->id);
});

it('does not identify zimbabwean students by passport number', function (): void {
    $context = hostelOccupantImportRoom();
    $student = hostelOccupantImportStudent('HMS-IMP-ZIM')['student'];
    ensureHostelOccupantImportIdType(IdTypeEnum::ZIMBABWEAN_ID_NUMBER);
    $student->update([
        'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
        'passport_number' => 'ZN9999999',
    ]);
    actingAsHostelOccupantImporter();

    $file = storeHostelOccupantImportFile([
        hostelOccupantImportCsvRow($student, $context, idNumber: '', passportNumber: 'ZN9999999', studentNumber: ''),
    ]);

    $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.errors.0', __('hms.import_occupants_zimbabwean_passport'));
});

it('shows sponsored and apprentice flags in preview', function (): void {
    $sponsoredApplication = createStudentReadyForHostelApplication('HMS-IMP-SPON-FLAG');
    $sponsored = $sponsoredApplication->student;
    $apprenticeApplication = createStudentReadyForHostelApplication('HMS-IMP-APP-FLAG');
    $apprentice = $apprenticeApplication->student;
    $context = hostelOccupantImportRoom();

    StudentSponsor::query()->create([
        'tenant_id' => $sponsored->tenant_id,
        'student_id' => $sponsored->id,
        'calendar_year' => 2025,
        'sponsor' => 'Ministry',
    ]);

    StudentApprentice::query()->create([
        'tenant_id' => $apprentice->tenant_id,
        'student_id' => $apprentice->id,
        'calendar_year' => 2025,
        'employer' => 'ZESA',
        'apprentice_number' => 'APR-FLAG',
    ]);

    actingAsHostelOccupantImporter();

    $sponsoredPreview = $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => storeHostelOccupantImportFile([
            hostelOccupantImportCsvRow($sponsored, $context),
        ]),
    ])->assertSuccessful()->json('rows.0');

    $apprenticePreview = $this->post(route('hostels.occupants.import.preview', $context['hostel']), [
        'file' => storeHostelOccupantImportFile([
            hostelOccupantImportCsvRow($apprentice, $context, section: 'B'),
        ]),
    ])->assertSuccessful()->json('rows.0');

    expect($sponsoredPreview['isSponsored'])->toBeTrue()
        ->and($sponsoredPreview['isApprentice'])->toBeFalse()
        ->and($sponsoredPreview['paymentSource'])->toBe('sponsored')
        ->and($apprenticePreview['isApprentice'])->toBeTrue()
        ->and($apprenticePreview['isSponsored'])->toBeFalse()
        ->and($apprenticePreview['paymentSource'])->toBe('apprentice');
});
