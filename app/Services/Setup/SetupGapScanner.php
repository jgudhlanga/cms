<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Contracts\Setup\SetupGapCheck;
use App\Models\Setup\SetupGap;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs every registered check and reconciles what they find against the stored gaps.
 *
 * A problem that is still present updates its existing row (so it is raised once, not nightly); a
 * problem that has been fixed is closed; a problem that returns after being fixed is reopened and
 * notified again. A check that throws is skipped without closing its gaps, so a broken detector never
 * silently reports "all clear".
 *
 * @phpstan-type ScanSummary array{raised: int, reopened: int, resolved: int, seen: int, failed: list<string>, raisedIds: list<int>}
 */
class SetupGapScanner
{
    /**
     * @param  iterable<SetupGapCheck>  $checks
     */
    public function __construct(private readonly iterable $checks) {}

    /**
     * @param  list<string>  $onlyCheckKeys  limit the run to these checks; empty runs them all
     * @return ScanSummary
     */
    public function scan(array $onlyCheckKeys = []): array
    {
        $summary = ['raised' => 0, 'reopened' => 0, 'resolved' => 0, 'seen' => 0, 'failed' => [], 'raisedIds' => []];

        foreach ($this->checks as $check) {
            if ($onlyCheckKeys !== [] && ! in_array($check->key()->value, $onlyCheckKeys, true)) {
                continue;
            }

            try {
                $results = collect($check->run());
            } catch (Throwable $exception) {
                report($exception);
                $summary['failed'][] = $check->key()->value;

                continue;
            }

            $outcome = $this->reconcile($check, $results);

            $summary['raised'] += $outcome['raised'];
            $summary['reopened'] += $outcome['reopened'];
            $summary['resolved'] += $outcome['resolved'];
            $summary['raisedIds'] = [...$summary['raisedIds'], ...$outcome['raisedIds']];
            $summary['seen'] += $results->count();
        }

        return $summary;
    }

    /**
     * @param  Collection<int, SetupGapResult>  $results
     * @return array{raised: int, reopened: int, resolved: int, raisedIds: list<int>}
     */
    private function reconcile(SetupGapCheck $check, Collection $results): array
    {
        $now = now();
        $raised = 0;
        $reopened = 0;
        $raisedIds = [];

        foreach ($results as $result) {
            $existing = SetupGap::query()->where('fingerprint', $result->fingerprint())->first();

            $attributes = [
                'tenant_id' => $result->tenantId,
                'check_key' => $check->key(),
                'institution_department_id' => $result->institutionDepartmentId,
                'severity' => $result->severity(),
                'title' => $result->title,
                'body' => $result->body,
                'url' => $result->url,
                'meta' => $result->meta,
                'last_seen_at' => $now,
            ];

            if ($existing === null) {
                $created = SetupGap::query()->create([
                    ...$attributes,
                    'fingerprint' => $result->fingerprint(),
                    'detected_at' => $now,
                ]);
                $raised++;
                $raisedIds[] = (int) $created->id;

                continue;
            }

            // A gap that was closed and has come back counts as new again, so it notifies afresh.
            if ($existing->resolved_at !== null) {
                $attributes['resolved_at'] = null;
                $attributes['notified_at'] = null;
                $attributes['detected_at'] = $now;
                $reopened++;
                $raisedIds[] = (int) $existing->id;
            }

            $existing->update($attributes);
        }

        $resolved = $this->closeMissing($check, $results->map(fn (SetupGapResult $row): string => $row->fingerprint())->all(), $now);

        return ['raised' => $raised, 'reopened' => $reopened, 'resolved' => $resolved, 'raisedIds' => $raisedIds];
    }

    /**
     * @param  list<string>  $seenFingerprints
     */
    private function closeMissing(SetupGapCheck $check, array $seenFingerprints, mixed $now): int
    {
        return SetupGap::query()
            ->open()
            ->where('check_key', $check->key()->value)
            ->when(
                $seenFingerprints !== [],
                fn ($query) => $query->whereNotIn('fingerprint', $seenFingerprints),
            )
            ->update(['resolved_at' => $now, 'updated_at' => DB::raw('updated_at')]);
    }
}
