<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\ModuleEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Integrations\PaymentGatewaySetting;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;
use App\Services\Integrations\PaymentGatewayConfig;
use App\Services\Rbac\RbacModuleStateService;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;

function paymentGatewaySettingsUser(array $permissions = ['view:payment-gateway-settings', 'update:payment-gateway-settings']): User
{
    $user = User::factory()->create(['password' => 'password']);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function unlockPaymentGatewaySession(): array
{
    return [PaymentGatewayConfig::UNLOCK_SESSION_KEY => time()];
}

/**
 * @param  list<string>  $only
 * @return array<string, string>
 */
function paymentGatewayInertiaHeaders(array $only = []): array
{
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
    ];

    if ($only !== []) {
        $headers['X-Inertia-Partial-Component'] = 'integrations/payment-gateway/Index';
        $headers['X-Inertia-Partial-Data'] = implode(',', $only);
    }

    return $headers;
}

function disableIntegrationsModule(): void
{
    Module::query()
        ->where('slug', ModuleEnum::INTEGRATIONS->slug())
        ->firstOrFail()
        ->update(['status' => false]);

    app(RbacModuleStateService::class)->clearCache();
}

test('integrations module is created from the enum seeder', function () {
    $module = Module::query()->where('slug', ModuleEnum::INTEGRATIONS->slug())->first();

    expect($module)->not->toBeNull()
        ->and($module->title)->toBe(ModuleEnum::INTEGRATIONS->value)
        ->and((bool) $module->status)->toBeTrue();
});

test('unauthorized users cannot open payment gateway settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('integrations.payment-gateway.index'))
        ->assertForbidden();
});

test('staff cannot open payment gateway settings when the integrations module is disabled', function () {
    $user = paymentGatewaySettingsUser();
    disableIntegrationsModule();

    $this->actingAs($user)
        ->get(route('integrations.payment-gateway.index'))
        ->assertForbidden();
});

test('locked payment gateway page is a cheap 200 without secrets or settings decrypt', function () {
    $user = paymentGatewaySettingsUser();

    PaymentGatewaySetting::query()->create([
        'gateway_name' => 'smile-n-pay',
        'gateway_api_key' => 'secret-db-key',
        'gateway_secret' => 'secret-db-secret',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('integrations.payment-gateway.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payment-gateway/Index')
            ->where('locked', true)
            ->where('gateway', null)
            ->where('canUpdate', true)
        );

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    expect(array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'payment_gateway_settings'),
    )))->toBe([]);
});

test('unlock with the account password then partial reload skips shared auth tables', function () {
    $user = paymentGatewaySettingsUser();

    $this->actingAs($user)
        ->postJson(route('integrations.payment-gateway.unlock'), ['password' => 'password'])
        ->assertOk()
        ->assertJson(['unlocked' => true]);

    $this->actingAs($user->fresh());

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->withSession(unlockPaymentGatewaySession())
        ->withHeaders(paymentGatewayInertiaHeaders(['gateway', 'locked']))
        ->get(route('integrations.payment-gateway.index'));

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    $response->assertOk()->assertJsonMissingPath('props.auth');

    expect(array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'intake_periods') || str_contains($sql, 'notifications'),
    )))->toBe([]);
});

test('wrong unlock password is rejected', function () {
    $user = paymentGatewaySettingsUser();

    $this->actingAs($user)
        ->postJson(route('integrations.payment-gateway.unlock'), ['password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('repeated wrong unlock passwords are rate limited', function () {
    $user = paymentGatewaySettingsUser();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)
            ->postJson(route('integrations.payment-gateway.unlock'), ['password' => 'wrong-password'])
            ->assertUnprocessable();
    }

    $this->actingAs($user)
        ->postJson(route('integrations.payment-gateway.unlock'), ['password' => 'wrong-password'])
        ->assertTooManyRequests();
});

test('full get with an unlocked session stays locked and does not decrypt settings', function () {
    $user = paymentGatewaySettingsUser();

    PaymentGatewaySetting::query()->create([
        'gateway_name' => 'smile-n-pay',
        'gateway_api_key' => 'super-secret-key',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->withSession(unlockPaymentGatewaySession())
        ->get(route('integrations.payment-gateway.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payment-gateway/Index')
            ->where('locked', true)
            ->where('gateway', null)
        );

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    expect(array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'payment_gateway_settings'),
    )))->toBe([]);
});

test('unlocked partial reload omits plaintext secrets', function () {
    $user = paymentGatewaySettingsUser();

    PaymentGatewaySetting::query()->create([
        'gateway_name' => 'smile-n-pay',
        'gateway_api_key' => 'super-secret-key',
        'gateway_secret' => 'super-secret-secret',
    ]);

    $this->actingAs($user)
        ->withSession(unlockPaymentGatewaySession())
        ->withHeaders(paymentGatewayInertiaHeaders(['gateway', 'locked']))
        ->get(route('integrations.payment-gateway.index'))
        ->assertOk()
        ->assertJsonPath('component', 'integrations/payment-gateway/Index')
        ->assertJsonPath('props.locked', false)
        ->assertJsonPath('props.gateway.gateway_name.value', 'smile-n-pay')
        ->assertJsonPath('props.gateway.gateway_api_key.isSet', true)
        ->assertJsonPath('props.gateway.gateway_api_key.source', 'database')
        ->assertJsonMissingPath('props.gateway.gateway_api_key.value')
        ->assertJsonMissingPath('props.gateway.gateway_secret.value');
});

test('locking the page forgets the unlock session', function () {
    $user = paymentGatewaySettingsUser();

    $this->actingAs($user)
        ->withSession(unlockPaymentGatewaySession())
        ->postJson(route('integrations.payment-gateway.lock'))
        ->assertOk()
        ->assertJson(['locked' => true]);

    $this->actingAs($user)
        ->withHeaders(paymentGatewayInertiaHeaders(['gateway', 'locked']))
        ->get(route('integrations.payment-gateway.index'))
        ->assertOk()
        ->assertJsonPath('props.locked', true)
        ->assertJsonPath('props.gateway', null);
});

test('saving credentials encrypts secrets, prefers the database over env, and forgets the cache', function () {
    $user = paymentGatewaySettingsUser();
    config()->set('custom.payments.payment-gateway.api_key', 'env-key');
    config()->set('custom.payments.payment-gateway.name', 'env-name');

    $config = app(PaymentGatewayConfig::class);
    $config->forget();
    expect($config->value('gateway_name'))->toBe('env-name');

    $this->actingAs($user)
        ->withSession(unlockPaymentGatewaySession())
        ->put(route('integrations.payment-gateway.update'), [
            'gateway_name' => 'db-gateway',
            'gateway_api_key' => 'db-api-key',
            'gateway_secret' => 'db-secret',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payment-gateway/Index')
            ->where('locked', false)
            ->where('gateway.gateway_name.value', 'db-gateway')
            ->where('gateway.gateway_api_key.isSet', true)
            ->missing('gateway.gateway_api_key.value')
        );

    $row = PaymentGatewaySetting::query()->first();

    expect($row)->not->toBeNull()
        ->and($row->gateway_name)->toBe('db-gateway')
        ->and($row->gateway_api_key)->toBe('db-api-key');

    $raw = DB::table('payment_gateway_settings')->value('gateway_api_key');
    expect($raw)->not->toBe('db-api-key')
        ->and(Crypt::decryptString($raw))->toBe('db-api-key');

    expect($config->value('gateway_api_key'))->toBe('db-api-key')
        ->and($config->value('gateway_name'))->toBe('db-gateway');
});

test('empty secret fields on save keep the current database secret', function () {
    $user = paymentGatewaySettingsUser();

    PaymentGatewaySetting::query()->create([
        'gateway_name' => 'smile-n-pay',
        'gateway_api_key' => 'keep-me',
    ]);

    $this->actingAs($user)
        ->withSession(unlockPaymentGatewaySession())
        ->put(route('integrations.payment-gateway.update'), [
            'gateway_name' => 'smile-n-pay',
            'gateway_api_key' => '',
        ])
        ->assertOk();

    expect(PaymentGatewaySetting::query()->first()?->gateway_api_key)->toBe('keep-me');
});

test('empty database fields fall back to env and the resolved map is cached until save', function () {
    config()->set('custom.payments.payment-gateway.api_key', 'env-only-key');
    config()->set('custom.payments.payment-gateway.name', 'env-only-name');

    $config = app(PaymentGatewayConfig::class);
    $config->forget();

    expect($config->value('gateway_api_key'))->toBe('env-only-key')
        ->and($config->resolved()['gateway_api_key']['source'])->toBe('env');

    DB::flushQueryLog();
    DB::enableQueryLog();
    $config->resolved();
    expect(count(array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains(strtolower($query['query']), 'payment_gateway_settings'),
    )))->toBe(0);
    DB::disableQueryLog();

    PaymentGatewaySetting::query()->create(['gateway_name' => 'after-save']);
    $config->forget();

    expect($config->value('gateway_name'))->toBe('after-save')
        ->and($config->value('gateway_api_key'))->toBe('env-only-key');
});

test('payment initiate uses database gateway credentials when they are set', function () {
    config([
        'custom.payments.payment-gateway.base_url' => 'https://env-gateway.test',
        'custom.payments.payment-gateway.api_key' => 'env-key',
        'custom.payments.payment-gateway.secret' => 'env-secret',
        'custom.payments.payment-gateway.name' => 'env-name',
        'custom.payments.payment-gateway.return_url' => 'https://app.test/feedback',
        'custom.payments.payment-gateway.cancel_url' => 'https://app.test/cancel',
        'custom.payments.payment-gateway.failure_url' => 'https://app.test/failure',
        'custom.payments.payment-gateway.result_url' => 'https://app.test/result',
    ]);

    PaymentGatewaySetting::query()->create([
        'gateway_base_url' => 'https://db-gateway.test',
        'gateway_api_key' => 'db-key',
        'gateway_secret' => 'db-secret',
        'gateway_name' => 'db-name',
    ]);

    app(PaymentGatewayConfig::class)->forget();
    app(PaymentGatewayConfig::class)->applyToRuntimeConfig();

    Http::fake([
        'db-gateway.test/payments/initiate-transaction' => Http::response(['ok' => true]),
    ]);

    Http::withHeaders([
        'x-api-key' => config('custom.payments.payment-gateway.api_key'),
        'x-api-secret' => config('custom.payments.payment-gateway.secret'),
    ])->post(config('custom.payments.payment-gateway.base_url').'/payments/initiate-transaction');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://db-gateway.test/payments/initiate-transaction'
            && $request->hasHeader('x-api-key', 'db-key')
            && $request->hasHeader('x-api-secret', 'db-secret');
    });
});

test('locked users cannot update payment gateway settings', function () {
    $user = paymentGatewaySettingsUser();

    $this->actingAs($user)
        ->put(route('integrations.payment-gateway.update'), [
            'gateway_name' => 'should-fail',
        ])
        ->assertForbidden();
});

test('bank statement fetch uses database credentials from the resolver', function () {
    config([
        'custom.bank-statements.base_url' => 'https://env-bank.test',
        'custom.bank-statements.usd.account_number' => 'env-usd',
        'custom.bank-statements.usd.password' => 'env-pass',
        'custom.bank-statements.retry_times' => 1,
        'custom.bank-statements.retry_sleep_ms' => 0,
    ]);

    PaymentGatewaySetting::query()->create([
        'bank_statements_base_url' => 'https://db-bank.test',
        'usd_account_number' => 'db-usd',
        'usd_password' => 'db-pass',
    ]);

    app(PaymentGatewayConfig::class)->forget();

    Http::fake([
        'db-bank.test/v1/statement' => Http::response(['transactions' => []]),
    ]);

    $result = app(FetchBankStatementService::class)->executeWithResult('usd', '2026-01-01', '2026-01-02');

    expect($result->exitCode)->toBe(0);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://db-bank.test/v1/statement'
            && $request['accountNumber'] === 'db-usd'
            && $request['password'] === 'db-pass';
    });
});

test('grant integrations permissions migration assigns abilities to the super user', function () {
    $newPermissions = [
        'view:integrations',
        'view:payment-gateway-settings',
        'update:payment-gateway-settings',
    ];

    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();

    $superUser = Role::query()
        ->where('name', RoleEnum::SUPER_USER->name())
        ->firstOrFail();

    $superUser->revokePermissionTo($newPermissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $migration = require database_path('migrations/2026_09_18_090100_grant_integrations_permissions.php');
    $migration->up();

    $superUser = $superUser->fresh();

    foreach ($newPermissions as $permission) {
        expect($superUser->hasPermissionTo($permission))->toBeTrue();
    }
});
