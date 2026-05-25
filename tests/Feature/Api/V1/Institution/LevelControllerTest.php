<?php

use App\Http\Requests\Institution\LevelRequest;
use App\Models\Institution\Level;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;

test('levels index includes calendar type attribute', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    Level::factory()->create([
        'calendar_type' => 'term',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.levels.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.attributes.calendarType', 'term');
});

test('level request requires a valid calendar type', function () {
    $validator = Validator::make(
        [
            'name' => fake()->unique()->word(),
            'calendar_type' => 'quarter',
        ],
        (new LevelRequest)->rules(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('calendar_type'))->toBeTrue();

    $missingCalendarTypeValidator = Validator::make(
        [
            'name' => fake()->unique()->word(),
        ],
        (new LevelRequest)->rules(),
    );

    expect($missingCalendarTypeValidator->fails())->toBeTrue()
        ->and($missingCalendarTypeValidator->errors()->has('calendar_type'))->toBeTrue();
});
