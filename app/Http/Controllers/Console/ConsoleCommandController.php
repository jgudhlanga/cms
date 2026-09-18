<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console;

use App\Enums\Console\ConsoleRunStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Console\DispatchConsoleCommandRequest;
use App\Jobs\Console\RunConsoleCommandJob;
use App\Models\Console\ConsoleCommandRun;
use App\Models\Users\User;
use App\Support\Console\ConsoleCommandRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConsoleCommandController extends Controller
{
    private const HISTORY_LIMIT = 25;

    public function __construct(
        private readonly ConsoleCommandRegistry $registry,
    ) {}

    /**
     * The allowlist is pure config, so a full visit (and the sidebar prefetch)
     * costs no queries. History is optional and arrives on a partial reload.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('console/Index', [
            'groups' => $this->registry->groups(),
            'canRun' => (bool) $request->user()?->can('runConsoleCommands'),
            'canRunDestructive' => (bool) $request->user()?->can('runDestructiveConsoleCommands'),
            'runs' => Inertia::optional(fn (): array => $this->history()),
        ]);
    }

    public function dispatchCommand(DispatchConsoleCommandRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if (! Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $request->string('password')->toString(),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $key = $request->string('command')->toString();
        $definition = $this->registry->find($key);

        if ($definition === null) {
            throw ValidationException::withMessages([
                'command' => __('console.unknown_command'),
            ]);
        }

        $parameters = $this->registry->toArtisanParameters($key, $request->parameterInput());

        $run = ConsoleCommandRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'command_key' => $key,
            'signature' => $definition['signature'],
            'parameters' => $parameters,
            'status' => ConsoleRunStatusEnum::QUEUED,
            'queued_by_user_id' => $user->id,
            'queued_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        $run->load('queuedBy:id,first_name,middle_name,last_name');

        activity('Console')
            ->performedOn($run)
            ->causedBy($user)
            ->event('dispatched')
            ->withProperties([
                'command_key' => $key,
                'signature' => $definition['signature'],
                'parameters' => $parameters,
                'risk' => $definition['risk'],
                'command_line' => $this->registry->commandLine($key, $parameters),
                'ip_address' => $request->ip(),
            ])
            ->log(__('console.activity_dispatched', ['command' => $definition['signature']]));

        RunConsoleCommandJob::dispatch($run->uuid, $this->registry->timeout($key));

        return response()->json([
            'run' => $this->presentRun($run),
        ], 202);
    }

    /**
     * Returns only the bytes the caller has not seen yet, so tailing a chatty
     * command stays cheap however long it runs.
     */
    public function output(ConsoleCommandRun $run, Request $request): JsonResponse
    {
        $this->authorize('viewConsole');

        $offset = max(0, (int) $request->integer('offset'));
        $output = $run->output ?? '';

        return response()->json([
            'status' => $run->status->value,
            'exitCode' => $run->exit_code,
            'error' => $run->error,
            'durationMs' => $run->duration_ms,
            'chunk' => substr($output, $offset),
            'offset' => strlen($output),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function history(): array
    {
        $runs = ConsoleCommandRun::query()
            ->with('queuedBy:id,first_name,middle_name,last_name')
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $history = [];

        foreach ($runs as $run) {
            $history[] = $this->presentRun($run);
        }

        return $history;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRun(ConsoleCommandRun $run): array
    {
        return [
            'uuid' => $run->uuid,
            'commandKey' => $run->command_key,
            'signature' => $run->signature,
            'commandLine' => $this->registry->commandLine($run->command_key, $run->parameters ?? []),
            'status' => $run->status->value,
            'statusLabel' => $run->status->label(),
            'statusColour' => $run->status->colour(),
            'exitCode' => $run->exit_code,
            'error' => $run->error,
            'durationMs' => $run->duration_ms,
            'queuedBy' => $run->queuedBy?->full_name,
            'queuedAt' => $run->queued_at?->toIso8601String(),
            'finishedAt' => $run->finished_at?->toIso8601String(),
        ];
    }
}
