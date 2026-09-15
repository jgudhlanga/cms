<?php

use App\Enums\Rbac\RoleEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Institution\Staff;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/*
 * Shared Inertia props run on every navigation, so they must stay cheap. These tests pin which
 * tables a plain page visit touches and prove partial reloads skip the auth, permission and
 * registration work entirely.
 *
 * Each request acts as a freshly loaded user, as production does, so relations loaded by an
 * earlier request cannot hide per-request queries.
 */

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

/**
 * @return array{0: TestResponse, 1: list<string>}
 */
function sharedPropsVisit(?User $user, string $url, array $headers = []): array
{
    $test = test();

    if ($user instanceof User) {
        $test->actingAs($user->fresh());
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $test->withHeaders(array_merge([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
    ], $headers))->get($url);

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    return [$response, $queries];
}

/**
 * @param  list<string>  $queries
 * @param  list<string>  $tables
 * @return list<string>
 */
function sharedPropsQueriesTouching(array $queries, array $tables): array
{
    return array_values(array_filter($queries, function (string $sql) use ($tables): bool {
        foreach ($tables as $table) {
            if (preg_match('/(from|join)\s+["`]'.preg_quote($table, '/').'["`]/', $sql) === 1) {
                return true;
            }
        }

        return false;
    }));
}

function sharedPropsStaffUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(['view:users', 'viewAny:students']);

    Staff::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'employee_number' => 'EMP-'.strtoupper(uniqid()),
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
    ]);

    return $user;
}

test('staff page visits do not build profile, contact or registration data', function () {
    $user = sharedPropsStaffUser();

    [$response, $queries] = sharedPropsVisit($user, route('settings.profile'));

    dump(['staff full load' => count($queries), 'tables' => sharedPropsQueriesTouching($queries, [
        'intake_periods', 'contacts', 'addresses', 'titles', 'genders', 'marital_statuses', 'countries', 'id_types',
    ])]);

    $response->assertOk()
        ->assertJsonPath('props.auth.user.id', $user->id)
        ->assertJsonMissingPath('props.ziggy')
        ->assertJsonMissingPath('props.returningStudent')
        ->assertJsonPath('props.registration', null);

    expect(sharedPropsQueriesTouching($queries, [
        'intake_periods', 'contacts', 'addresses', 'titles', 'genders', 'marital_statuses', 'countries', 'id_types',
    ]))->toBe([]);
});

test('partial reloads skip auth, permission and registration queries', function () {
    $user = sharedPropsStaffUser();

    sharedPropsVisit($user, route('settings.profile'))[0]->assertOk();

    [$response, $queries] = sharedPropsVisit($user, route('settings.profile'), [
        'X-Inertia-Partial-Component' => 'settings/Profile',
        'X-Inertia-Partial-Data' => 'status',
    ]);

    dump(['staff partial reload' => count($queries), 'sql' => $queries]);

    $response->assertOk()->assertJsonMissingPath('props.auth');

    expect(sharedPropsQueriesTouching($queries, [
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'staff', 'students', 'tenants', 'statuses', 'intake_periods', 'notifications',
    ]))->toBe([]);
});

test('student page visits share registration availability without profile lookups', function () {
    [$user] = createReturningStudentUser();

    [$response, $queries] = sharedPropsVisit($user, route('settings.profile'));

    dump([
        'student full load' => count($queries),
        'intake queries' => count(sharedPropsQueriesTouching($queries, ['intake_periods'])),
        'profile tables' => sharedPropsQueriesTouching($queries, ['contacts', 'addresses', 'titles', 'genders', 'marital_statuses']),
    ]);

    $response->assertOk()
        ->assertJsonPath('props.auth.user.attributes.hasStudentProfile', true)
        ->assertJsonStructure(['props' => ['registration' => ['isOpen', 'regularOpen', 'continuousOpen', 'maintenanceUrl']]])
        ->assertJsonMissingPath('props.returningStudent');

    expect(sharedPropsQueriesTouching($queries, ['contacts', 'addresses', 'titles', 'genders', 'marital_statuses']))->toBe([])
        ->and(count(sharedPropsQueriesTouching($queries, ['intake_periods'])))->toBeLessThanOrEqual(3);
});

test('guest pages receive registration availability', function () {
    [$response, $queries] = sharedPropsVisit(null, route('login'));

    dump(['guest full load' => count($queries)]);

    $response->assertOk()
        ->assertJsonPath('props.auth.user', null)
        ->assertJsonStructure(['props' => ['registration' => ['isOpen', 'maintenanceUrl']]]);

    expect(count(sharedPropsQueriesTouching($queries, ['intake_periods'])))->toBeLessThanOrEqual(3);
});
