<?php

use App\Models\Acl\Permission;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Users\User;

test('guests are redirected when visiting finance exchange rates page', function () {
    $this->get(route('finance.exchange-rates.index'))->assertRedirect('/login');
});

test('authenticated users without finance settings permission cannot visit finance exchange rates page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('finance.exchange-rates.index'))
        ->assertForbidden();
});

test('authenticated users with finance settings permission can visit finance exchange rates page', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('view:finance-settings', 'web');
    $user->givePermissionTo('view:finance-settings');

    $this->actingAs($user)
        ->get(route('finance.exchange-rates.index'))
        ->assertSuccessful();
});

test('store exchange rate requires create finance settings permission', function () {
    $user = User::factory()->create();

    $payload = [
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ];

    $this->actingAs($user)
        ->post(route('finance.exchange-rates.store'), $payload)
        ->assertForbidden();

    Permission::findOrCreate('create:finance-settings', 'web');
    $user->givePermissionTo('create:finance-settings');

    $this->actingAs($user)
        ->post(route('finance.exchange-rates.store'), $payload)
        ->assertSuccessful();

    $record = FinanceExchangeRate::query()->latest('id')->first();

    expect($record)->not->toBeNull()
        ->and($record->date)->toBe('2026-03-25')
        ->and($record->currency_from)->toBe('USD')
        ->and($record->currency_to)->toBe('ZWG')
        ->and($record->rate)->toBe('26.380300');
});

test('update exchange rate requires update finance settings permission', function () {
    $user = User::factory()->create();

    $rate = FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $payload = [
        'date' => '2026-03-26',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '27.0000',
    ];

    $this->actingAs($user)
        ->put(route('finance.exchange-rates.update', $rate->id), $payload)
        ->assertForbidden();

    Permission::findOrCreate('update:finance-settings', 'web');
    $user->givePermissionTo('update:finance-settings');

    $this->actingAs($user)
        ->put(route('finance.exchange-rates.update', $rate->id), $payload)
        ->assertSuccessful();

    $rate->refresh();

    expect($rate->date)->toBe('2026-03-26')
        ->and($rate->rate)->toBe('27.0000');
});

test('archive exchange rate requires delete finance settings permission', function () {
    $user = User::factory()->create();

    $rate = FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.exchange-rates.destroy', $rate->id))
        ->assertForbidden();

    Permission::findOrCreate('delete:finance-settings', 'web');
    $user->givePermissionTo('delete:finance-settings');

    $this->actingAs($user)
        ->delete(route('finance.exchange-rates.destroy', $rate->id))
        ->assertSuccessful();

    expect($rate->refresh()->deleted_at)->not->toBeNull();
});

test('restore exchange rate requires restore finance settings permission', function () {
    $user = User::factory()->create();

    $rate = FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $rate->delete();
    expect($rate->fresh()->deleted_at)->not->toBeNull();

    $this->actingAs($user)
        ->put(route('finance.exchange-rates.restore', $rate->id))
        ->assertForbidden();

    Permission::findOrCreate('restore:finance-settings', 'web');
    $user->givePermissionTo('restore:finance-settings');

    $this->actingAs($user)
        ->put(route('finance.exchange-rates.restore', $rate->id))
        ->assertSuccessful();

    expect($rate->fresh()->deleted_at)->toBeNull();
});

test('force delete exchange rate requires force delete finance settings permission', function () {
    $user = User::factory()->create();

    $rate = FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.exchange-rates.force-delete', $rate->id))
        ->assertForbidden();

    Permission::findOrCreate('forceDelete:finance-settings', 'web');
    $user->givePermissionTo('forceDelete:finance-settings');

    $this->actingAs($user)
        ->delete(route('finance.exchange-rates.force-delete', $rate->id))
        ->assertSuccessful();

    expect(FinanceExchangeRate::query()->find($rate->id))->toBeNull();
});
