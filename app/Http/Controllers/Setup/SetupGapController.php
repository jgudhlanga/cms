<?php

declare(strict_types=1);

namespace App\Http\Controllers\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Http\Controllers\Controller;
use App\Models\Setup\SetupGap;
use App\Models\Users\User;
use App\Services\Setup\SetupGapScanner;
use App\Services\Setup\SetupGapVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Feeds the header setup alert: the open configuration problems the signed-in user is allowed to see.
 */
class SetupGapController extends Controller
{
    private const int LIST_LIMIT = 30;

    public function __construct(private readonly SetupGapVisibility $visibility) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->boolean('count_only')) {
            return response()->json(['openCount' => $this->visibility->openCountFor($user)]);
        }

        return response()->json($this->payload($user));
    }

    /**
     * Re-runs the checks now, so someone who has just fixed a problem sees it drop off the list without
     * waiting for the queued re-check or the nightly run. Only the checks this user is allowed to see
     * are run, and only what they are allowed to see comes back.
     */
    public function refresh(Request $request, SetupGapScanner $scanner): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $allowed = $this->allowedChecks($user);

        if ($allowed->isEmpty()) {
            abort(403);
        }

        $validated = $request->validate([
            'check' => ['nullable', 'string', Rule::in($allowed->map(fn (SetupGapCheckEnum $check): string => $check->value)->all())],
        ]);

        $scanner->scan(
            isset($validated['check'])
                ? [$validated['check']]
                : $allowed->map(fn (SetupGapCheckEnum $check): string => $check->value)->all(),
        );

        return response()->json($this->payload($user));
    }

    /**
     * @return Collection<int, SetupGapCheckEnum>
     */
    private function allowedChecks(User $user): Collection
    {
        return collect(SetupGapCheckEnum::cases())
            ->filter(fn (SetupGapCheckEnum $check): bool => $user->can($check->permission()))
            ->values();
    }

    /**
     * @return array{gaps: list<array<string, mixed>>, openCount: int}
     */
    private function payload(User $user): array
    {
        $gaps = $this->visibility->visibleTo($user)
            ->with('institutionDepartment.department')
            ->get()
            // Worst first, then most recent: severity is a string column, so ordering happens here.
            ->sortByDesc(fn (SetupGap $gap): array => [$gap->severity->weight(), $gap->detected_at?->getTimestamp() ?? 0])
            ->take(self::LIST_LIMIT)
            ->map(fn (SetupGap $gap): array => [
                'id' => (string) $gap->id,
                'check' => $gap->check_key->value,
                'checkLabel' => $gap->check_key->label(),
                'severity' => $gap->severity->value,
                'title' => $gap->title,
                'body' => $gap->body,
                'url' => $gap->url,
                'department' => $gap->institutionDepartment?->department?->name,
                'detectedAt' => $gap->detected_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'gaps' => $gaps,
            'openCount' => $this->visibility->openCountFor($user),
        ];
    }
}
