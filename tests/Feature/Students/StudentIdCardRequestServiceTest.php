<?php

declare(strict_types=1);

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Exceptions\Students\InvalidIdCardRequestTransitionException;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use App\Services\AccountPurge\AccountPurgeSnapshotBuilder;
use App\Services\AccountPurge\StudentRelationPurgeService;
use App\Services\Students\PdfCardPrinter;
use App\Services\Students\PhysicalCardPrinter;
use App\Services\Students\StudentIdCardRequestService;
use App\Support\Rbac\PermissionRegistry;
use App\Support\Students\StudentIdCardSerialGenerator;

test('id card admin permissions are registered', function () {
    expect(PermissionRegistry::allValues())->toContain(
        'viewAny:student-id-card-requests',
        'view:student-id-card-requests',
        'review:student-id-card-requests',
        'print:student-id-card-requests',
        'issue:student-id-card-requests',
        'viewAuditTrail:student-id-card-requests',
        'view:student-id-card-settings',
        'update:student-id-card-settings',
    );
});

test('new card request is free and pending', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);

    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);

    expect($request->status)->toBe(IdCardRequestStatusEnum::PENDING)
        ->and($request->fee_ledger_id)->toBeNull()
        ->and($request->photo_media_id)->not->toBeNull();
});

test('lost card request awaits payment and creates a ledger', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);

    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::LOST);

    expect($request->status)->toBe(IdCardRequestStatusEnum::AWAITING_PAYMENT)
        ->and($request->fee_ledger_id)->not->toBeNull();

    $this->assertDatabaseHas('ledgers', [
        'id' => $request->fee_ledger_id,
        'type' => 'invoice',
        'payment_status' => 'pending',
        'ledgerable_type' => StudentIdCardRequest::class,
        'ledgerable_id' => $request->id,
    ]);
});

test('mark paid moves awaiting payment to pending', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $request = $service->submit($student, IdCardRequestReasonEnum::LOST);

    $updated = $service->markPaid($request->fresh());

    expect($updated->status)->toBe(IdCardRequestStatusEnum::PENDING);
});

test('paid receipt ledger marks an unpaid id card request pending', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $request = $service->submit($student, IdCardRequestReasonEnum::DAMAGED);
    $invoice = $request->feeLedger;

    $request->ledgerTransactions()->create([
        'tenant_id' => $request->tenant_id,
        'fee_type_id' => $invoice->fee_type_id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => $invoice->amount,
        'currency' => 'USD',
        'system_reference' => 'IDCARD-PAY-'.$request->id,
    ]);

    expect($request->fresh()->status)->toBe(IdCardRequestStatusEnum::PENDING);
});

test('cannot submit a second active request', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $service->submit($student, IdCardRequestReasonEnum::NEW);

    expect(fn () => $service->submit($student, IdCardRequestReasonEnum::NEW))
        ->toThrow(InvalidIdCardRequestTransitionException::class);
});

test('cannot approve an unpaid request', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $request = $service->submit($student, IdCardRequestReasonEnum::LOST);
    $admin = createIdCardStaff((int) $tenant->id, ['review:student-id-card-requests']);

    expect(fn () => $service->approve($request, $admin))
        ->toThrow(InvalidIdCardRequestTransitionException::class);
});

test('cannot print a pending request', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $request = $service->submit($student, IdCardRequestReasonEnum::NEW);
    $admin = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    expect(fn () => $service->print($request, $admin))
        ->toThrow(InvalidIdCardRequestTransitionException::class);
});

test('submit snapshots the latest student photo', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student, 'first.jpg');
    $latest = attachIdCardPhoto($student, 'second.jpg');

    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);
    $requestPhoto = $request->photo;

    expect($requestPhoto)->not->toBeNull()
        ->and($requestPhoto->id)->not->toBe($latest->id)
        ->and($requestPhoto->file_name)->toBe($latest->file_name);
});

test('serial numbers include the student number and request id', function () {
    ['student' => $student] = createIdCardStudent(['student_number' => 'H123456']);
    $request = new StudentIdCardRequest;
    $request->id = 42;

    $serial = app(StudentIdCardSerialGenerator::class)->generate($student, $request);

    expect($serial)->toBe('HPC-H123456-42');
});

test('print assigns a unique serial and reprint keeps it', function () {
    ['tenant' => $tenant, 'student' => $firstStudent] = createIdCardStudent();
    ['student' => $secondStudent] = createIdCardStudent(['tenant_id' => $tenant->id]);
    attachIdCardPhoto($firstStudent);
    attachIdCardPhoto($secondStudent);

    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, ['review:student-id-card-requests', 'print:student-id-card-requests']);

    $first = $service->approve($service->submit($firstStudent, IdCardRequestReasonEnum::NEW), $admin);
    $second = $service->approve($service->submit($secondStudent, IdCardRequestReasonEnum::NEW), $admin);

    $firstPrint = $service->print($first, $admin);
    $secondPrint = $service->print($second, $admin);
    $reprint = $service->print($first->fresh(), $admin);

    expect($firstPrint->serialNumber)->not->toBe($secondPrint->serialNumber)
        ->and($reprint->serialNumber)->toBe($firstPrint->serialNumber)
        ->and($first->fresh()->serial_number)->toBe($firstPrint->serialNumber)
        ->and($firstPrint->pdfBinary)->not->toBe('');
});

test('printed pdf uses landscape cr80 page size', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
    ]);

    $request = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);
    $pdf = $service->print($request, $admin)->pdfBinary;

    expect($pdf)->toContain('%PDF');
    expect(preg_match('/\/Type\s*\/Pages.*?\/Count\s+(\d+)/s', $pdf, $pageCount))->toBe(1);
    expect((int) $pageCount[1])->toBe(1);
    expect(preg_match('/\/MediaBox\s*\[([^\]]+)\]/', $pdf, $matches))->toBe(1);

    $box = array_map('floatval', preg_split('/\s+/', trim($matches[1]), -1, PREG_SPLIT_NO_EMPTY));

    expect($box)->toHaveCount(4);

    $width = $box[2] - $box[0];
    $height = $box[3] - $box[1];

    expect($width)->toBeGreaterThan($height)
        ->and($width)->toEqualWithDelta(PdfCardPrinter::CARD_WIDTH_PT, 2)
        ->and($height)->toEqualWithDelta(PdfCardPrinter::CARD_HEIGHT_PT, 2);
});

test('issuing a card records the previous active issued card', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
        'issue:student-id-card-requests',
    ]);

    $first = $service->submit($student, IdCardRequestReasonEnum::NEW);
    $service->approve($first, $admin);
    $service->print($first->fresh(), $admin);
    $issued = $service->issue($first->fresh(), $admin);

    $second = $service->submit($student, IdCardRequestReasonEnum::NEW);
    $service->approve($second, $admin);
    $service->print($second->fresh(), $admin);
    $replacement = $service->issue($second->fresh(), $admin);

    expect($issued->status)->toBe(IdCardRequestStatusEnum::ISSUED)
        ->and($replacement->supersedes_request_id)->toBe($issued->id);
});

test('account purge deletes id card requests and snapshots them', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);

    $snapshot = app(AccountPurgeSnapshotBuilder::class)->buildForStudent($student);

    expect($snapshot['id_card_requests'])->toHaveCount(1)
        ->and($snapshot['id_card_requests'][0]['id'])->toBe($request->id);

    app(StudentRelationPurgeService::class)->purge($student);

    expect(StudentIdCardRequest::withTrashed()->where('student_id', $student->id)->exists())->toBeFalse()
        ->and($student->fresh()->getMedia(Student::ID_PHOTO_COLLECTION))->toHaveCount(0);
});

test('physical printer adapter is not implemented', function () {
    ['student' => $student] = createIdCardStudent();
    $request = StudentIdCardRequest::factory()->printed()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'serial_number' => 'HPC-TEST-1',
    ]);

    expect(fn () => app(PhysicalCardPrinter::class)->print($request))
        ->toThrow(RuntimeException::class);
});

test('export approved assigns serials and keeps status approved', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, ['review:student-id-card-requests']);
    $approved = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);

    expect($service->hasApprovedReadyToPrint())->toBeTrue();

    $result = $service->exportApproved();
    $fresh = $approved->fresh();

    expect($result)->not->toBeNull()
        ->and($result->pdfBinary)->toContain('%PDF')
        ->and($fresh->status)->toBe(IdCardRequestStatusEnum::APPROVED)
        ->and($fresh->serial_number)->not->toBeNull()
        ->and($fresh->printed_at)->toBeNull();

    expect(preg_match('/\/Type\s*\/Pages.*?\/Count\s+(\d+)/s', $result->pdfBinary, $pageCount))->toBe(1);
    expect((int) $pageCount[1])->toBe(2);
});

test('export approved ignores pending cards', function () {
    ['student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);

    expect(app(StudentIdCardRequestService::class)->hasApprovedReadyToPrint())->toBeFalse()
        ->and(app(StudentIdCardRequestService::class)->exportApproved())->toBeNull();
});
