<?php

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Services\HMS\HostelAccommodationPaymentMatcher;
use Carbon\CarbonImmutable;

it('matches bank credits that include the student number and accommodation', function (): void {
    $studentNumber = 'HMS-BANK-ACC-1';
    $start = CarbonImmutable::parse(now()->subMonth()->toDateString())->startOfDay();
    $end = CarbonImmutable::parse(now()->addMonths(5)->toDateString())->endOfDay();

    ZBBankStatement::query()->create([
        'tran_number_asc' => 'T-ASC-MATCH',
        'tran_number_desc' => 'T-DESC-MATCH',
        'transaction_id' => 'TXN-MATCH',
        'transaction_sr_id' => 'TSR-MATCH',
        'transaction_date' => now()->toDateTimeString(),
        'debit_credit_flag' => 'C',
        'narration' => "ACCOMMODATION fees {$studentNumber}",
    ]);

    $matcher = app(HostelAccommodationPaymentMatcher::class);

    expect($matcher->hasBankEvidence($studentNumber, $start, $end))->toBeTrue()
        ->and($matcher->hasBankEvidence($studentNumber, $start->subYear(), $start->subDay()))->toBeFalse()
        ->and($matcher->hasBankEvidence('OTHER-NUMBER', $start, $end))->toBeFalse();
});

it('does not match bank credits without accommodation in the description', function (): void {
    $studentNumber = 'HMS-BANK-TUIT';
    $start = CarbonImmutable::parse(now()->subMonth()->toDateString())->startOfDay();
    $end = CarbonImmutable::parse(now()->addMonths(5)->toDateString())->endOfDay();

    ZBBankStatement::query()->create([
        'tran_number_asc' => 'T-ASC-TUIT',
        'tran_number_desc' => 'T-DESC-TUIT',
        'transaction_id' => 'TXN-TUIT',
        'transaction_sr_id' => 'TSR-TUIT',
        'transaction_date' => now()->toDateTimeString(),
        'debit_credit_flag' => 'C',
        'narration' => "Tuition {$studentNumber}",
    ]);

    expect(app(HostelAccommodationPaymentMatcher::class)->hasBankEvidence($studentNumber, $start, $end))->toBeFalse();
});

it('matches paid accommodation ledger receipts for the student user', function (): void {
    $student = createStudentForAllocationIndexTest();
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
        'amount' => 200,
        'system_reference' => 'ACC-MATCH-1',
        'payment_date' => now(),
    ]);

    $window = [
        'start' => CarbonImmutable::parse(now()->subMonth()->toDateString())->startOfDay(),
        'end' => CarbonImmutable::parse(now()->addMonths(5)->toDateString())->endOfDay(),
    ];

    expect(app(HostelAccommodationPaymentMatcher::class)->hasLedgerEvidence($student->fresh(['user']), $window))->toBeTrue();
});
