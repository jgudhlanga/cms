<?php

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('receipt lookups return the same ledger whether or not receipts are eager loaded', function () {
    $application = createVerifiedStudentApplication('RCPT-'.strtoupper(Str::random(6)));
    $otherApplication = createVerifiedStudentApplication('RCPT-'.strtoupper(Str::random(6)));
    $user = $application->student()->firstOrFail()->user()->firstOrFail();

    $applicationFee = FeeType::query()->firstOrCreate(['slug' => FeeTypeEnum::APPLICATION_FEE->slug()], ['name' => 'Application fee']);
    $tuitionFee = FeeType::query()->firstOrCreate(['slug' => FeeTypeEnum::TUITION_FEE->slug()], ['name' => 'Tuition fee']);

    $ledger = fn (array $attributes): Ledger => Ledger::query()->forceCreate(array_merge([
        'tenant_id' => $application->tenant_id,
        'ledgerable_type' => $user->getMorphClass(),
        'ledgerable_id' => $user->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 20,
        'currency' => 'USD',
        'system_reference' => 'RCPT-'.Str::uuid(),
    ], $attributes));

    $ledger(['fee_type_id' => $applicationFee->id, 'student_application_id' => $application->id, 'created_at' => now()->subDays(3)]);
    $newestPaidApplicationFee = $ledger(['fee_type_id' => $applicationFee->id, 'student_application_id' => $application->id, 'created_at' => now()->subDay()]);
    $ledger(['fee_type_id' => $applicationFee->id, 'student_application_id' => $application->id, 'payment_status' => 'pending', 'created_at' => now()]);
    $ledger(['fee_type_id' => $applicationFee->id, 'student_application_id' => $otherApplication->id, 'created_at' => now()]);

    $ledger(['fee_type_id' => $tuitionFee->id, 'created_at' => now()->subDays(2)]);
    $newestTuitionReceipt = $ledger(['fee_type_id' => $tuitionFee->id, 'created_at' => now()->subHour()]);
    $ledger(['fee_type_id' => $tuitionFee->id, 'type' => 'invoice', 'created_at' => now()]);

    $queried = StudentApplication::query()->findOrFail($application->id);
    $eagerLoaded = StudentApplication::query()
        ->with(['receiptLedgers.feeType', 'student.user.receiptLedgers.feeType'])
        ->findOrFail($application->id);

    expect($queried->receipt(FeeTypeEnum::APPLICATION_FEE)?->id)->toBe($newestPaidApplicationFee->id)
        ->and($eagerLoaded->receipt(FeeTypeEnum::APPLICATION_FEE)?->id)->toBe($newestPaidApplicationFee->id)
        ->and($queried->receipt(FeeTypeEnum::TUITION_FEE)?->id)->toBe($newestTuitionReceipt->id)
        ->and($eagerLoaded->receipt(FeeTypeEnum::TUITION_FEE)?->id)->toBe($newestTuitionReceipt->id);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $eagerLoaded->hasPaid(FeeTypeEnum::APPLICATION_FEE);
    $eagerLoaded->hasPaid(FeeTypeEnum::TUITION_FEE);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBe(0);
});
