<?php

declare(strict_types=1);

namespace App\Queries\Examinations;

use App\Enums\Students\StudentExamResultComment;
use App\Models\Examinations\ExaminationResult;
use App\Models\Students\StudentExamResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExaminationResultQuery
{
    /**
     * @param  array{
     *     session?: string|null,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     surname?: string|null,
     *     first_names?: string|null,
     *     candidate_number?: string|null,
     * }  $filters
     */
    public function filtered(array $filters): Builder
    {
        return ExaminationResult::query()
            ->when(
                filled($filters['session'] ?? null),
                fn (Builder $query): Builder => $query->where('session', $filters['session']),
            )
            ->when(
                filled($filters['discipline'] ?? null),
                fn (Builder $query): Builder => $query->where('discipline', $filters['discipline']),
            )
            ->when(
                filled($filters['subject_code'] ?? null),
                fn (Builder $query): Builder => $query->where('subject_code', $filters['subject_code']),
            )
            ->when(
                filled($filters['surname'] ?? null),
                fn (Builder $query): Builder => $query->where('surname', 'like', '%'.$filters['surname'].'%'),
            )
            ->when(
                filled($filters['first_names'] ?? null),
                fn (Builder $query): Builder => $query->where('first_names', 'like', '%'.$filters['first_names'].'%'),
            )
            ->when(
                filled($filters['candidate_number'] ?? null),
                fn (Builder $query): Builder => $query->where('candidate_number', 'like', '%'.$filters['candidate_number'].'%'),
            );
    }

    /**
     * Distinct candidate counts grouped by normalized course comment.
     *
     * @param  array{session?: string|null, discipline?: string|null, subject_code?: string|null}  $filters
     * @return array<string, int>
     */
    public function statusCountsByComment(array $filters): array
    {
        $candidateComments = $this->filtered($filters)
            ->select([
                'candidate_number',
                DB::raw('UPPER(TRIM(MAX(course_comment))) as status_comment'),
            ])
            ->groupBy('candidate_number');

        return DB::query()
            ->fromSub($candidateComments, 'candidate_statuses')
            ->select([
                'status_comment',
                DB::raw('COUNT(*) as aggregate'),
            ])
            ->whereNotNull('status_comment')
            ->where('status_comment', '!=', '')
            ->groupBy('status_comment')
            ->pluck('aggregate', 'status_comment')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * Per-module pass rates using distinct candidates (AWARD + PROCEED).
     *
     * @param  array{session?: string|null, discipline?: string|null, subject_code?: string|null}  $filters
     * @return Collection<int, object{subject_code: string, subject: string|null, total: int|string, passed: int|string}>
     */
    public function modulePassRates(array $filters): Collection
    {
        $passComments = [
            StudentExamResultComment::Award->value,
            StudentExamResultComment::Proceed->value,
        ];
        $passList = "'".implode("','", $passComments)."'";

        return $this->filtered($filters)
            ->select([
                'subject_code',
                DB::raw('MAX(subject) as subject'),
                DB::raw('COUNT(DISTINCT candidate_number) as total'),
                DB::raw(
                    "COUNT(DISTINCT CASE WHEN UPPER(TRIM(course_comment)) IN ({$passList}) THEN candidate_number END) as passed"
                ),
            ])
            ->whereNotNull('subject_code')
            ->where('subject_code', '!=', '')
            ->groupBy('subject_code')
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * Students who claimed/viewed results online for candidates in the filtered dump.
     *
     * @param  array{session?: string|null, discipline?: string|null, subject_code?: string|null}  $filters
     */
    public function onlineViewedCount(array $filters): int
    {
        if (! filled($filters['session'] ?? null)) {
            return 0;
        }

        $candidateNumbers = $this->filtered($filters)
            ->select('candidate_number')
            ->distinct();

        return StudentExamResult::query()
            ->where('session', $filters['session'])
            ->whereIn('candidate_number', $candidateNumbers)
            ->count();
    }

    public function latestSession(): ?string
    {
        $row = ExaminationResult::query()
            ->select(['session', 'session_date'])
            ->whereNotNull('session')
            ->where('session', '!=', '')
            ->orderByDesc('session_date')
            ->orderByDesc('session')
            ->first();

        return $row?->session;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function sessionOptions(): array
    {
        return ExaminationResult::query()
            ->selectRaw('session, MAX(session_date) as session_date')
            ->whereNotNull('session')
            ->where('session', '!=', '')
            ->groupBy('session')
            ->orderByDesc('session_date')
            ->orderByDesc('session')
            ->get()
            ->map(fn (ExaminationResult $row): array => [
                'value' => (string) $row->session,
                'label' => $this->sessionLabel(
                    (string) $row->session,
                    $row->session_date ? (string) $row->session_date : null,
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function disciplineOptions(?string $session): array
    {
        return ExaminationResult::query()
            ->select('discipline')
            ->whereNotNull('discipline')
            ->where('discipline', '!=', '')
            ->when(
                filled($session),
                fn (Builder $query): Builder => $query->where('session', $session),
            )
            ->distinct()
            ->orderBy('discipline')
            ->pluck('discipline')
            ->map(fn (string $discipline): array => [
                'value' => $discipline,
                'label' => $discipline,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function subjectOptions(?string $session, ?string $discipline): array
    {
        return ExaminationResult::query()
            ->selectRaw('subject_code, MAX(subject) as subject')
            ->whereNotNull('subject_code')
            ->where('subject_code', '!=', '')
            ->when(
                filled($session),
                fn (Builder $query): Builder => $query->where('session', $session),
            )
            ->when(
                filled($discipline),
                fn (Builder $query): Builder => $query->where('discipline', $discipline),
            )
            ->groupBy('subject_code')
            ->orderBy('subject_code')
            ->get()
            ->map(function (ExaminationResult $row): array {
                $code = (string) $row->subject_code;
                $name = trim((string) ($row->subject ?? ''));

                return [
                    'value' => $code,
                    'label' => $name !== '' ? "{$code} — {$name}" : $code,
                ];
            })
            ->values()
            ->all();
    }

    public function sessionLabel(string $session, ?string $sessionDate): string
    {
        if ($sessionDate !== null && $sessionDate !== '') {
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $sessionDate, $matches) === 1) {
                return $matches[1];
            }

            try {
                return Carbon::parse($sessionDate)->toDateString();
            } catch (\Throwable) {
                return $sessionDate;
            }
        }

        return $session;
    }

    /**
     * Resolve filters for the search index: default session to latest when absent.
     *
     * @param  array{
     *     session?: string|null,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     surname?: string|null,
     *     first_names?: string|null,
     *     candidate_number?: string|null,
     *     compare_session?: string|null,
     * }  $input
     * @return array{
     *     session: string|null,
     *     discipline: string|null,
     *     subject_code: string|null,
     *     surname: string|null,
     *     first_names: string|null,
     *     candidate_number: string|null,
     *     compare_session: string|null,
     * }
     */
    public function resolveFilters(array $input, bool $defaultSession = true): array
    {
        $session = $this->nullableString($input['session'] ?? null);

        if ($defaultSession && $session === null) {
            $session = $this->latestSession();
        }

        $discipline = $this->nullableString($input['discipline'] ?? null);
        $subjectCode = $this->nullableString($input['subject_code'] ?? null);
        $compareSession = $this->nullableString($input['compare_session'] ?? null);

        if ($discipline !== null && ! $this->disciplineExists($session, $discipline)) {
            $discipline = null;
            $subjectCode = null;
        }

        if ($subjectCode !== null && ! $this->subjectExists($session, $discipline, $subjectCode)) {
            $subjectCode = null;
        }

        if ($compareSession !== null && $compareSession === $session) {
            $compareSession = null;
        }

        return [
            'session' => $session,
            'discipline' => $discipline,
            'subject_code' => $subjectCode,
            'surname' => $this->nullableString($input['surname'] ?? null),
            'first_names' => $this->nullableString($input['first_names'] ?? null),
            'candidate_number' => $this->nullableString($input['candidate_number'] ?? null),
            'compare_session' => $compareSession,
        ];
    }

    private function disciplineExists(?string $session, string $discipline): bool
    {
        return ExaminationResult::query()
            ->where('discipline', $discipline)
            ->when(
                filled($session),
                fn (Builder $query): Builder => $query->where('session', $session),
            )
            ->exists();
    }

    private function subjectExists(?string $session, ?string $discipline, string $subjectCode): bool
    {
        return ExaminationResult::query()
            ->where('subject_code', $subjectCode)
            ->when(
                filled($session),
                fn (Builder $query): Builder => $query->where('session', $session),
            )
            ->when(
                filled($discipline),
                fn (Builder $query): Builder => $query->where('discipline', $discipline),
            )
            ->exists();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
