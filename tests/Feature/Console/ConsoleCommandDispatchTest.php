<?php

declare(strict_types=1);

use App\Enums\Shared\ModuleEnum;
use App\Jobs\Console\RunConsoleCommandJob;
use App\Models\Rbac\Module;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

test('the console module is created from the enum seeder', function () {
    $module = Module::query()->where('slug', ModuleEnum::CONSOLE->slug())->first();

    expect($module)->not->toBeNull()
        ->and($module->title)->toBe(ModuleEnum::CONSOLE->value)
        ->and((bool) $module->status)->toBeTrue();
});

test('unauthorized users cannot open the console', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console.index'))
        ->assertForbidden();
});

test('the console index is a cheap 200 without loading run history', function () {
    $user = consoleUser(['view:console']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('console.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('console/Index')
            ->has('groups')
            ->where('canRun', false)
            ->missing('runs')
        );

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    expect(array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'console_command_runs'),
    )))->toBe([]);
});

test('a partial reload loads recent runs', function () {
    $user = consoleUser(['view:console']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->withHeaders(consoleInertiaHeaders(['runs']))
        ->get(route('console.index'))
        ->assertOk()
        ->assertJsonPath('component', 'console/Index')
        ->assertJsonStructure(['props' => ['runs']]);

    $queries = array_map(fn (array $query): string => strtolower($query['query']), DB::getQueryLog());
    DB::disableQueryLog();

    expect(array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'console_command_runs'),
    )))->not->toBe([]);
});

test('a wrong password is rejected', function () {
    $user = consoleUser();

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-audit-phase-data',
            'password' => 'not-the-password',
            'parameters' => ['--limit' => 10],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('repeated dispatch attempts are rate limited', function () {
    $user = consoleUser();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->actingAs($user)
            ->postJson(route('console.dispatch'), [
                'command' => 'students-audit-phase-data',
                'password' => 'wrong',
            ])
            ->assertUnprocessable();
    }

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-audit-phase-data',
            'password' => 'wrong',
        ])
        ->assertStatus(429);
});

test('an unknown command key is rejected', function () {
    $user = consoleUser();

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'migrate',
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['command']);
});

test('a missing required argument is rejected', function () {
    $user = consoleUser(['view:console', 'run:console-commands', 'run:destructive-console-commands']);

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'statements-get-request',
            'password' => 'password',
            'parameters' => [
                'startDate' => '2026-01-01',
                'endDate' => '2026-01-31',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parameters.accountType']);
});

test('an out of allowlist account type is rejected', function () {
    $user = consoleUser(['view:console', 'run:console-commands', 'run:destructive-console-commands']);

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'statements-get-request',
            'password' => 'password',
            'parameters' => [
                'accountType' => 'eur',
                'startDate' => '2026-01-01',
                'endDate' => '2026-01-31',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parameters.accountType']);
});

test('a csv path cannot escape the restore directory', function () {
    $user = consoleUser(['view:console', 'run:console-commands', 'run:destructive-console-commands']);

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-restore-mode-reassign-bug',
            'password' => 'password',
            'parameters' => [
                '--mode' => '--dry-run',
                '--csv' => '../secrets.csv',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parameters.--csv']);
});

test('a successful dispatch queues the job and writes an activity log entry', function () {
    Queue::fake();
    $user = consoleUser();

    $response = $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-audit-phase-data',
            'password' => 'password',
            'parameters' => ['--limit' => 10],
        ])
        ->assertStatus(202)
        ->assertJsonPath('run.status', 'queued')
        ->assertJsonPath('run.signature', 'students:audit-phase-data');

    $uuid = $response->json('run.uuid');

    expect($uuid)->toBeString();

    $this->assertDatabaseHas('console_command_runs', [
        'uuid' => $uuid,
        'command_key' => 'students-audit-phase-data',
        'queued_by_user_id' => $user->id,
    ]);

    Queue::assertPushed(RunConsoleCommandJob::class, fn (RunConsoleCommandJob $job): bool => $job->runUuid === $uuid);

    expect(Activity::query()->where('log_name', 'Console')->where('event', 'dispatched')->exists())->toBeTrue();
});

test('view permission alone cannot dispatch', function () {
    $user = consoleUser(['view:console']);

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-audit-phase-data',
            'password' => 'password',
        ])
        ->assertForbidden();
});

test('run permission without the destructive permission cannot dispatch activitylog clean', function () {
    $user = consoleUser(['view:console', 'run:console-commands']);

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'activitylog-clean',
            'password' => 'password',
            'parameters' => ['--days' => 365],
        ])
        ->assertForbidden();
});

test('a disabled console module blocks every route', function () {
    $user = consoleUser(['view:console', 'run:console-commands']);
    disableConsoleModule();

    $this->actingAs($user)
        ->get(route('console.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('console.dispatch'), [
            'command' => 'students-audit-phase-data',
            'password' => 'password',
        ])
        ->assertForbidden();
});
