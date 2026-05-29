<?php

use App\Models\Finance\FinanceTransactionQuery;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

it('allows student to create missing transaction query without proof and view own queries', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manageOwnStudentFinancialDetails:students');
    $student = createStudentForFinanceQuery($user, 'STU-Q-001');
    Sanctum::actingAs($user);

    $createResponse = $this->post(
        route('v1.financials.student.transaction-queries.store', ['student' => $student->id]),
        [
            'payment_reference' => 'PAY-001',
            'description' => 'I cannot find this on my statement',
        ]
    );

    $createResponse->assertCreated();
    expect(FinanceTransactionQuery::query()->count())->toBe(1);

    $listResponse = $this->getJson(route('v1.financials.student.transaction-queries.index', ['student' => $student->id]));
    $listResponse->assertOk();
    $listResponse->assertJsonPath('data.0.attributes.paymentReference', 'PAY-001');
    $listResponse->assertJsonPath('data.0.attributes.status', 'submitted');
});

it('reconciles a query, updates narration and notifies student', function () {
    $studentUser = User::factory()->create();
    $student = createStudentForFinanceQuery($studentUser, 'STU-Q-002');

    $admin = User::factory()->create();
    $admin->givePermissionTo('update:finances');

    $statement = ZBBankStatement::query()->create([
        'tran_number_asc' => 'RA-1',
        'tran_number_desc' => 'RD-1',
        'transaction_id' => 'RTXN-1',
        'transaction_sr_id' => 'RSR-1',
        'transaction_date' => '2026-05-10T12:00:00',
        'debit_credit_flag' => 'C',
        'amount_credit' => '100.00',
        'iso_currency_code' => 'USD',
        'reference' => 'PAY-002',
        'narration' => 'Payment PAY-002',
    ]);

    $query = FinanceTransactionQuery::query()->create([
        'student_id' => $student->id,
        'payment_reference' => 'PAY-002',
        'status' => 'submitted',
    ]);

    $query->addMedia(UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'))
        ->toMediaCollection('financial-documents');

    Sanctum::actingAs($admin);
    $response = $this->patchJson(
        route('v1.financials.reconciliation.transaction-queries.reconcile', ['transactionQuery' => $query->id]),
        ['bank_statement_id' => $statement->id]
    );

    $response->assertOk();
    $query->refresh();
    $statement->refresh();

    expect($query->status?->value)->toBe('reconciled')
        ->and($query->bank_statement_id)->toBe($statement->id)
        ->and((string) $statement->pipe5_details)->toContain($student->student_number);
});
