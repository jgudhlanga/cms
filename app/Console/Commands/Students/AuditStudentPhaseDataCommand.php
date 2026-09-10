<?php

declare(strict_types=1);

namespace App\Console\Commands\Students;

use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use Illuminate\Console\Command;

/**
 * Read-only audit of student phase data.
 *
 * Two jobs:
 *   1. Pre-deploy sizing. StudentEnrolment::currentStudentSemester() now orders by the pinned
 *      programme phase position rather than the calendar slug ordinal. Calendar slugs wrap within
 *      the year, so the old ordering could not tell Year 1 Sem 1 from Year 2 Sem 1. This reports
 *      how many enrolments change their "current phase" under the new ordering, because that value
 *      feeds class lists, progression and exam awards.
 *   2. Existing damage. Reconciliation used to overwrite a phase pin in place when the target
 *      shared a calendar slot with an earlier year, destroying the earlier record. Enrolments with
 *      a gap in their phase history are the survivors of that.
 */
class AuditStudentPhaseDataCommand extends Command
{
    protected $signature = 'students:audit-phase-data
        {--limit=25 : How many example rows to print per finding}';

    protected $description = 'Read-only audit of student phase pins: current-phase drift and gaps left by the old reconciliation overwrite';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info('Auditing student phase data. This command writes nothing.');
        $this->newLine();

        $enrolments = StudentEnrolment::query()
            ->with(['studentSemesters.semester', 'studentSemesters.programmeSemester', 'student'])
            ->has('studentSemesters')
            ->get();

        $this->line("Enrolments with phase records: {$enrolments->count()}");
        $this->newLine();

        $drift = [];
        $gaps = [];
        $unpinned = 0;

        foreach ($enrolments as $enrolment) {
            $rows = $enrolment->studentSemesters;

            $unpinned += $rows->filter(
                static fn (StudentSemester $row): bool => $row->programme_semester_id === null,
            )->count();

            $old = $rows
                ->sortBy(static fn (StudentSemester $row): int => self::slugOrdinal($row))
                ->last();

            $new = $rows
                ->sortBy(static fn (StudentSemester $row): array => [self::phasePosition($row), (int) $row->id])
                ->last();

            if ($old !== null && $new !== null && (int) $old->id !== (int) $new->id) {
                $drift[] = [
                    (string) $enrolment->id,
                    (string) ($enrolment->student?->student_number ?? '—'),
                    (string) ($old->programmeSemester?->name ?? $old->semester?->slug ?? '—'),
                    (string) ($new->programmeSemester?->name ?? $new->semester?->slug ?? '—'),
                ];
            }

            // A pin at position N with no record for some earlier position means the history for
            // that phase is gone (or was never written).
            $positions = $rows
                ->map(static fn (StudentSemester $row): ?int => $row->programmeSemester?->position !== null
                    ? (int) $row->programmeSemester->position
                    : null)
                ->filter(static fn (?int $position): bool => $position !== null)
                ->map(static fn (?int $position): int => (int) $position)
                ->sort()
                ->values();

            if ($positions->isEmpty()) {
                continue;
            }

            $missing = [];

            for ($position = 1; $position < (int) $positions->last(); $position++) {
                if (! $positions->contains($position)) {
                    $missing[] = $position;
                }
            }

            if ($missing !== []) {
                $gaps[] = [
                    (string) $enrolment->id,
                    (string) ($enrolment->student?->student_number ?? '—'),
                    implode(', ', $positions->all()),
                    implode(', ', $missing),
                ];
            }
        }

        $this->reportDrift($drift, $limit);
        $this->reportGaps($gaps, $limit);

        $this->line("Phase records with no programme_semester_id pin: {$unpinned}");
        $this->line('  These fall back to calendar-slug ordering, which cannot distinguish academic years.');
        $this->newLine();

        $this->info('Done. Nothing was modified.');

        return self::SUCCESS;
    }

    /**
     * @param  list<list<string>>  $drift
     */
    private function reportDrift(array $drift, int $limit): void
    {
        $count = count($drift);

        $this->line("1. Enrolments whose current phase changes under the new ordering: {$count}");

        if ($count === 0) {
            $this->line('   Nothing to review — the read change is a no-op on this data.');
            $this->newLine();

            return;
        }

        $this->line('   Review these before deploying: current phase drives class lists and progression.');
        $this->table(
            ['Enrolment', 'Student', 'Current phase (old)', 'Current phase (new)'],
            array_slice($drift, 0, $limit),
        );

        if ($count > $limit) {
            $this->line('   ... and '.($count - $limit).' more. Raise --limit to see them.');
        }

        $this->newLine();
    }

    /**
     * @param  list<list<string>>  $gaps
     */
    private function reportGaps(array $gaps, int $limit): void
    {
        $count = count($gaps);

        $this->line("2. Enrolments with a gap in their phase history: {$count}");

        if ($count === 0) {
            $this->line('   No gaps found.');
            $this->newLine();

            return;
        }

        $this->line('   Likely the old reconciliation overwrite: a later phase was pinned onto the row');
        $this->line('   holding an earlier phase that shared a calendar slot.');
        $this->table(
            ['Enrolment', 'Student', 'Positions held', 'Positions missing'],
            array_slice($gaps, 0, $limit),
        );

        if ($count > $limit) {
            $this->line('   ... and '.($count - $limit).' more. Raise --limit to see them.');
        }

        $this->newLine();
    }

    /**
     * The ordering used before the fix: trailing number of the calendar slug.
     */
    private static function slugOrdinal(StudentSemester $row): int
    {
        $parts = explode('-', (string) ($row->semester?->slug ?? ''));

        return (int) end($parts);
    }

    /**
     * The ordering used after the fix: pinned programme phase position, slug as fallback.
     */
    private static function phasePosition(StudentSemester $row): int
    {
        $position = $row->programmeSemester?->position;

        return $position !== null ? (int) $position : self::slugOrdinal($row);
    }
}
