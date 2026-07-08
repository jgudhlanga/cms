<?php

use App\Enums\HMS\HostelEligibilityContextEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\HmsSetting;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\StudentApplication;
use App\Services\HMS\HostelApplicationEligibilityService;

it('passes tuition eligibility when bank statement payments are recorded', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('ELIG-TUITION-PAYMENT');
    $student = $studentApplication->student;

    HmsSetting::resolveForTenant(TenantEnum::HARARE_POLY->id())->update([
        'require_tuition_paid' => true,
        'require_full_time_study' => false,
        'require_address_outside_campus' => false,
        'require_accommodation_paid' => false,
    ]);

    ZBBankStatement::query()->create([
        'tran_number_asc' => 'ELIG-TP-1',
        'tran_number_desc' => 'ELIG-TP-1',
        'transaction_id' => 'ELIG-TP-TXN-1',
        'transaction_sr_id' => 'ELIG-TP-SR-1',
        'transaction_date' => '2026-03-30T12:00:00',
        'debit_credit_flag' => 'C',
        'amount_credit' => '500.00',
        'iso_currency_code' => 'USD',
        'narration' => 'Payment '.$student->student_number,
    ]);

    $rules = app(HostelApplicationEligibilityService::class)->evaluate(
        $student->fresh(['latestEnrolment.studentApplication']),
        context: HostelEligibilityContextEnum::APPLICATION,
    );

    $tuitionRule = collect($rules)->firstWhere('key', 'tuition_paid');

    expect($tuitionRule)->not->toBeNull()
        ->and($tuitionRule['passed'])->toBeTrue();
});

it('fails tuition eligibility when no bank payments or staff confirmation exist', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('ELIG-TUITION-NONE');
    $student = $studentApplication->student;

    HmsSetting::resolveForTenant(TenantEnum::HARARE_POLY->id())->update([
        'require_tuition_paid' => true,
        'require_full_time_study' => false,
        'require_address_outside_campus' => false,
        'require_accommodation_paid' => false,
    ]);

    $rules = app(HostelApplicationEligibilityService::class)->evaluate(
        $student->fresh(['latestEnrolment.studentApplication']),
        context: HostelEligibilityContextEnum::APPLICATION,
    );

    $tuitionRule = collect($rules)->firstWhere('key', 'tuition_paid');

    expect($tuitionRule)->not->toBeNull()
        ->and($tuitionRule['passed'])->toBeFalse();
});

it('passes tuition eligibility when tuition fee is confirmed by staff', function (): void {
    $studentApplication = createStudentReadyForHostelApplication('ELIG-TUITION-CONFIRMED');
    $studentApplication->update(['tuition_fee_confirmed' => true]);
    $student = $studentApplication->student;

    HmsSetting::resolveForTenant(TenantEnum::HARARE_POLY->id())->update([
        'require_tuition_paid' => true,
        'require_full_time_study' => false,
        'require_address_outside_campus' => false,
        'require_accommodation_paid' => false,
    ]);

    $rules = app(HostelApplicationEligibilityService::class)->evaluate(
        $student->fresh(['latestEnrolment.studentApplication']),
        context: HostelEligibilityContextEnum::APPLICATION,
    );

    $tuitionRule = collect($rules)->firstWhere('key', 'tuition_paid');

    expect($tuitionRule)->not->toBeNull()
        ->and($tuitionRule['passed'])->toBeTrue();
});
