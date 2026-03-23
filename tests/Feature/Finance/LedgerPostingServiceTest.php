<?php

use App\Enums\Finance\FinanceAccountType;
use App\Models\Finance\Account;
use App\Models\Finance\Invoice;
use App\Models\Finance\Journal;
use App\Models\Finance\LedgerEntry;
use App\Models\Finance\Receipt;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Finance\LedgerPostingService;

function seedPostingAccounts(int $tenantId): void
{
    Account::factory()->create([
        'tenant_id' => $tenantId,
        'code' => LedgerPostingService::ACCOUNT_AR,
        'name' => 'Accounts Receivable',
        'type' => FinanceAccountType::Asset,
    ]);
    Account::factory()->create([
        'tenant_id' => $tenantId,
        'code' => LedgerPostingService::ACCOUNT_REVENUE_TUITION,
        'name' => 'Tuition Revenue',
        'type' => FinanceAccountType::Revenue,
    ]);
    Account::factory()->create([
        'tenant_id' => $tenantId,
        'code' => LedgerPostingService::ACCOUNT_BANK,
        'name' => 'Bank',
        'type' => FinanceAccountType::Asset,
    ]);
}

it('posts invoice recognition with balanced ledger entries', function () {
    $tenant = Tenant::query()->firstOrFail();
    seedPostingAccounts($tenant->id);
    $invoice = Invoice::factory()->create(['tenant_id' => $tenant->id, 'amount' => '250.50']);

    app(LedgerPostingService::class)->postInvoiceRecognized($invoice);

    $entries = LedgerEntry::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->orderBy('id')
        ->get();

    expect($entries)->toHaveCount(2);
    $arLine = $entries->firstWhere('account_code', LedgerPostingService::ACCOUNT_AR);
    $revLine = $entries->firstWhere('account_code', LedgerPostingService::ACCOUNT_REVENUE_TUITION);
    expect((string) $arLine->debit)->toBe('250.50')
        ->and((string) $arLine->credit)->toBe('0.00')
        ->and((string) $revLine->debit)->toBe('0.00')
        ->and((string) $revLine->credit)->toBe('250.50');
});

it('posts receipt against accounts receivable with balanced ledger entries', function () {
    $tenant = Tenant::query()->firstOrFail();
    seedPostingAccounts($tenant->id);
    $receipt = Receipt::factory()->create(['tenant_id' => $tenant->id, 'amount' => '100.00']);

    app(LedgerPostingService::class)->postReceiptAgainstAr($receipt);

    $entries = LedgerEntry::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->get();

    expect($entries)->toHaveCount(2);
    $bankLine = $entries->firstWhere('account_code', LedgerPostingService::ACCOUNT_BANK);
    $arLine = $entries->firstWhere('account_code', LedgerPostingService::ACCOUNT_AR);
    expect((string) $bankLine->debit)->toBe('100.00')
        ->and((string) $arLine->credit)->toBe('100.00');
});

it('throws when journal lines are not balanced', function () {
    $tenant = Tenant::query()->firstOrFail();
    seedPostingAccounts($tenant->id);
    $journal = Journal::factory()->create(['tenant_id' => $tenant->id]);

    expect(fn () => app(LedgerPostingService::class)->postJournalAdjustment($journal, [
        ['account_code' => LedgerPostingService::ACCOUNT_AR, 'debit' => '10.00', 'credit' => '0.00'],
        ['account_code' => LedgerPostingService::ACCOUNT_BANK, 'debit' => '5.00', 'credit' => '0.00'],
    ]))->toThrow(InvalidArgumentException::class, 'not balanced');
});

it('throws when finance account codes are missing for the tenant', function () {
    $tenant = Tenant::query()->firstOrFail();
    $invoice = Invoice::factory()->create(['tenant_id' => $tenant->id]);

    expect(fn () => app(LedgerPostingService::class)->postInvoiceRecognized($invoice))
        ->toThrow(InvalidArgumentException::class, 'not defined');
});

it('scopes finance accounts to the authenticated user tenant', function () {
    $tenantA = Tenant::query()->orderBy('id')->firstOrFail();
    $tenantB = Tenant::query()->orderBy('id')->skip(1)->firstOrFail();

    $user = User::factory()->create(['tenant_id' => $tenantA->id]);

    Account::factory()->create([
        'tenant_id' => $tenantA->id,
        'code' => 'SCOPE_A',
        'name' => 'Tenant A account',
        'type' => FinanceAccountType::Asset,
    ]);
    Account::factory()->create([
        'tenant_id' => $tenantB->id,
        'code' => 'SCOPE_B',
        'name' => 'Tenant B account',
        'type' => FinanceAccountType::Asset,
    ]);

    $this->actingAs($user);

    expect(Account::query()->pluck('code')->all())->toBe(['SCOPE_A']);
});
