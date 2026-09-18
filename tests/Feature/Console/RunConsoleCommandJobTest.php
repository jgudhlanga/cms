<?php

declare(strict_types=1);

use App\Enums\Console\ConsoleRunStatusEnum;
use App\Jobs\Console\RunConsoleCommandJob;
use App\Models\Console\ConsoleCommandRun;
use App\Support\Console\ConsoleRunOutputStream;
use Illuminate\Support\Str;
use Symfony\Component\Console\Exception\CommandNotFoundException;

function makeConsoleRun(array $overrides = []): ConsoleCommandRun
{
    return ConsoleCommandRun::query()->create([
        'uuid' => (string) Str::uuid(),
        'command_key' => 'students-audit-phase-data',
        'signature' => 'students:audit-phase-data',
        'parameters' => ['--limit' => 5, '--no-interaction' => true],
        'status' => ConsoleRunStatusEnum::QUEUED,
        'queued_at' => now(),
        ...$overrides,
    ]);
}

test('the job records success with captured output', function () {
    $run = makeConsoleRun();

    (new RunConsoleCommandJob($run->uuid, 60))->handle();

    $run->refresh();

    expect($run->status)->toBe(ConsoleRunStatusEnum::SUCCEEDED)
        ->and($run->exit_code)->toBe(0)
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->output)->not->toBeEmpty();
});

test('a throwing command is marked failed with a finished timestamp', function () {
    $run = makeConsoleRun([
        'signature' => 'this-command-does-not-exist',
    ]);

    expect(fn () => (new RunConsoleCommandJob($run->uuid, 60))->handle())
        ->toThrow(CommandNotFoundException::class);

    $run->refresh();

    expect($run->status)->toBe(ConsoleRunStatusEnum::FAILED)
        ->and($run->error)->not->toBeNull()
        ->and($run->finished_at)->not->toBeNull();
});

test('long output is persisted in full', function () {
    $run = makeConsoleRun([
        'status' => ConsoleRunStatusEnum::RUNNING,
    ]);

    $stream = new ConsoleRunOutputStream($run);
    $payload = str_repeat('a', 80);
    $stream->writeln($payload);
    $stream->flush();

    $run->refresh();

    expect($run->output_truncated)->toBeFalse()
        ->and($run->output)->toContain($payload);
});
