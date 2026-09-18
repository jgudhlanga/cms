<?php

declare(strict_types=1);

use App\Enums\Console\ConsoleRunStatusEnum;
use App\Models\Console\ConsoleCommandRun;
use App\Models\Users\User;
use Illuminate\Support\Str;

test('the output endpoint returns only the unread chunk', function () {
    $user = consoleUser(['view:console']);
    $run = ConsoleCommandRun::query()->create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $user->tenant_id,
        'command_key' => 'students-audit-phase-data',
        'signature' => 'students:audit-phase-data',
        'parameters' => ['--no-interaction' => true],
        'status' => ConsoleRunStatusEnum::RUNNING,
        'queued_by_user_id' => $user->id,
        'queued_at' => now(),
        'started_at' => now(),
        'output' => 'hello world',
    ]);

    $this->actingAs($user)
        ->getJson(route('console.runs.output', ['run' => $run->uuid, 'offset' => 6]))
        ->assertOk()
        ->assertJsonPath('status', 'running')
        ->assertJsonPath('chunk', 'world')
        ->assertJsonPath('offset', 11);
});

test('users without view permission cannot tail output', function () {
    $owner = consoleUser(['view:console']);
    $stranger = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $run = ConsoleCommandRun::query()->create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $owner->tenant_id,
        'command_key' => 'students-audit-phase-data',
        'signature' => 'students:audit-phase-data',
        'parameters' => [],
        'status' => ConsoleRunStatusEnum::SUCCEEDED,
        'queued_by_user_id' => $owner->id,
        'queued_at' => now(),
        'output' => 'secret',
    ]);

    $this->actingAs($stranger)
        ->getJson(route('console.runs.output', $run))
        ->assertForbidden();
});
