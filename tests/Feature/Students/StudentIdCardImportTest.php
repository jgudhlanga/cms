<?php

declare(strict_types=1);

use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Services\Students\StudentIdCardPhotoService;
use App\Services\Students\StudentIdCardRequestService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @param  list<list<string|null>>  $rows
 */
function storeStudentIdCardImportFile(array $rows): UploadedFile
{
    $relativePath = 'test-id-card-import-'.uniqid().'.csv';
    $fullPath = storage_path('app/'.$relativePath);
    $handle = fopen($fullPath, 'w');
    fputcsv($handle, [
        'Student Number',
        'ID Number',
        'Passport Number',
    ]);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return new UploadedFile($fullPath, 'id-cards.csv', 'text/csv', null, true);
}

test('print permission is required to open the id card import page', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['viewAny:student-id-card-requests']);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.import'))
        ->assertForbidden();
});

test('staff with print permission can open the import page and download a csv template', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.import'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('students/id-card-requests/Import'));

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.import.template'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('import preview returns no rows for a header-only file', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([]),
        ])
        ->assertOk()
        ->assertJsonPath('summary.total', 0)
        ->assertJsonPath('summary.ready', 0);
});

test('import preview marks an unknown student as invalid', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([['NO-SUCH-STUDENT', null, null]]),
        ])
        ->assertOk()
        ->assertJsonPath('summary.ready', 0)
        ->assertJsonPath('rows.0.isSelectable', false);
});

test('import preview finds a student by passport and requires a photo', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[null, null, $student->passport_number]]),
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.studentId', $student->id)
        ->assertJsonPath('rows.0.isSelectable', false);
});

test('import preview finds a print-folder photo by passport and process imports it into media', function () {
    Storage::fake('id-card-photos');
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);
    $stem = app(StudentIdCardPhotoService::class)->identityStem($student);
    expect($stem)->not->toBeNull()
        ->and($student->latestIdPhoto())->toBeNull();

    $image = UploadedFile::fake()->image('pool.jpg', 400, 500);
    Storage::disk('id-card-photos')->put(
        $stem.'.jpg',
        (string) file_get_contents($image->getRealPath()),
    );

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[null, null, $student->passport_number]]),
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.hasPhoto', true)
        ->assertJsonPath('rows.0.isSelectable', true);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.process'), [
            'rows' => [
                ['rowNumber' => 2, 'studentId' => $student->id],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    expect($student->fresh()->latestIdPhoto())->not->toBeNull();
});

test('import preview is ready when the student has a photo and no active request', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[$student->student_number, null, null]]),
        ])
        ->assertOk()
        ->assertJsonPath('summary.ready', 1)
        ->assertJsonPath('rows.0.isSelectable', true);
});

test('import preview blocks an active request', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[$student->student_number, null, null]]),
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.isSelectable', false);
});

test('import preview blocks duplicate students in the same file', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([
                [$student->student_number, null, null],
                [null, null, $student->passport_number],
            ]),
        ])
        ->assertOk()
        ->assertJsonPath('summary.ready', 0)
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.1.isSelectable', false);
});

test('import preview blocks a zimbabwean student matched by passport only', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent([
        'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
        'id_number' => '63-888888A63',
        'passport_number' => 'ZWPASS88',
    ]);
    attachIdCardPhoto($student);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[null, null, 'ZWPASS88']]),
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.isSelectable', false);
});

test('import process creates an approved request with a photo snapshot', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.process'), [
            'rows' => [
                ['rowNumber' => 2, 'studentId' => $student->id],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    $this->assertDatabaseHas('student_id_card_requests', [
        'student_id' => $student->id,
        'status' => IdCardRequestStatusEnum::APPROVED->value,
        'reason' => IdCardRequestReasonEnum::NEW->value,
        'reviewed_by' => $staff->id,
    ]);

    expect($student->fresh()->idCardRequests()->first()->photo_media_id)->not->toBeNull();
});

test('import process skips a student who gained an active request after preview', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.import.process'), [
            'rows' => [
                ['rowNumber' => 2, 'studentId' => $student->id],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('summary.imported', 0)
        ->assertJsonPath('summary.skipped', 1);
});

test('import preview allows a student whose only request is issued', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
        'issue:student-id-card-requests',
    ]);
    $request = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);
    $service->print($request, $admin);
    $service->issue($request->fresh(), $admin);

    $this->actingAs($admin)
        ->post(route('admin.students.id-card-requests.import.preview'), [
            'file' => storeStudentIdCardImportFile([[$student->student_number, null, null]]),
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.isSelectable', true);
});
