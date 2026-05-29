<?php

use App\Models\Acl\Permission;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Users\User;
use App\Services\Finance\StudentFinancialStatementPdfService;
use Illuminate\Support\Facades\DB;

it('includes home address and guardian contact in pdf payload', function () {
    $user = User::factory()->create();
    $student = createStudentForFinanceQuery($user, 'STU-PDF-ADDR');

    $student->addresses()->create([
        'tenant_id' => $student->tenant_id,
        'address_1' => '1696',
        'address_2' => 'Harare Drive',
        'address_3' => 'Marlborough',
        'address_4' => 'Harare',
        'address_is_main' => 1,
    ]);

    $relationshipId = DB::table('relationships')->insertGetId([
        'name' => 'Parent',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nextOfKin = $student->nextOfKins()->make([
        'name' => 'Charity Mudzedze',
        'relationship_id' => $relationshipId,
    ]);
    $nextOfKin->tenant_id = $student->tenant_id;
    $nextOfKin->save();

    $nextOfKin->contacts()->create([
        'tenant_id' => $student->tenant_id,
        'phone_number' => '0772878418',
    ]);

    $payload = app(StudentFinancialStatementPdfService::class)->assemble($student);
    $contactRows = collect($payload['contactInformation'])->keyBy('label');

    expect($contactRows['Home Address']['value'])->toBe('1696, Harare Drive, Marlborough, Harare')
        ->and($contactRows['Guardian Contact']['value'])->toBe('0772878418');
});

it('redirects guests from transaction statement export', function () {
    $user = User::factory()->create();
    $student = createStudentForFinanceQuery($user, 'STU-PDF-GUEST');

    $this->get(route('documents.transaction-statement', ['student' => $student->id]))
        ->assertRedirect('/login');
});

it('allows finance user to export transaction statement pdf', function () {
    $studentNumber = 'STU-PDF-FIN-01';

    seedLedgerStatementForStudent($studentNumber);

    $studentUser = User::factory()->create();
    $student = createStudentForFinanceQuery($studentUser, $studentNumber);

    $financeUser = User::factory()->create([
        'tenant_id' => $student->tenant_id,
    ]);
    Permission::findOrCreate('view:finances', 'web');
    $financeUser->givePermissionTo('view:finances');

    $response = $this->actingAs($financeUser)
        ->get(route('documents.transaction-statement', ['student' => $student->id]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('allows student to export own transaction statement pdf', function () {
    $studentNumber = 'STU-PDF-OWN-01';

    seedLedgerStatementForStudent($studentNumber);

    $studentUser = User::factory()->create();
    $student = createStudentForFinanceQuery($studentUser, $studentNumber);

    $response = $this->actingAs($studentUser)
        ->get(route('documents.transaction-statement', ['student' => $student->id]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('forbids unauthorized user from exporting transaction statement pdf', function () {
    $studentUser = User::factory()->create();
    $student = createStudentForFinanceQuery($studentUser, 'STU-PDF-FORBID');

    $otherUser = User::factory()->create([
        'tenant_id' => $student->tenant_id,
    ]);

    $this->actingAs($otherUser)
        ->get(route('documents.transaction-statement', ['student' => $student->id]))
        ->assertForbidden();
});

function seedLedgerStatementForStudent(string $studentNumber): void
{
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-30',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    ZBBankStatement::query()->create([
        'tran_number_asc' => 'PDF-A-1',
        'tran_number_desc' => 'PDF-D-1',
        'transaction_id' => 'PDF-TXN-1',
        'transaction_sr_id' => 'PDF-SR-1',
        'transaction_date' => '2026-03-30T12:00:00',
        'debit_credit_flag' => 'D',
        'amount_debit' => '500.00',
        'iso_currency_code' => 'USD',
        'narration' => 'Tuition '.$studentNumber,
    ]);
}
