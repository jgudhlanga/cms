<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The single definition of which enrolments must confirm a study position this period. The prompt,
 * the admin view, reporting and the nightly command all go through here.
 *
 * In scope: an enrolment in the current calendar year of its level's calendar type, not awarded or
 * disqualified, on an offering that has programme phases, and the newest enrolment of its application.
 * Deferred, absent and referred students are asked too; their status is preserved on write.
 */
class StudyPositionScope
{
    // Same aliases StudentEnrolmentProgressionService::statusIdBySlug() accepts.
    private const AWARD_SLUGS = [StudentEnrolmentProgressionService::STATUS_AWARD, 'completed'];

    private const DISQUALIFIED_SLUGS = [StudentEnrolmentProgressionService::STATUS_DISQUALIFIED];

    public function __construct(
        private readonly CurrentStudyPeriodResolver $periods,
    ) {}

    /**
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query  an Eloquent or query builder over student_enrolments (optionally aliased)
     * @return TBuilder
     */
    public function constrain(BuilderContract $query, string $table = 'student_enrolments'): BuilderContract
    {
        $groups = $this->periodGroups();

        if ($groups === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereNull("{$table}.deleted_at");

        // Pair each type's current-year periods with the levels on that calendar type, so an enrolment
        // filed against a calendar of the wrong type never slips in.
        $query->where(function ($inner) use ($groups, $table): void {
            foreach ($groups as [$periodIds, $type]) {
                $inner->orWhere(function ($group) use ($periodIds, $type, $table): void {
                    $group->whereIn("{$table}.academic_calendar_id", $periodIds)
                        ->whereIn("{$table}.department_level_id", function (QueryBuilder $levels) use ($type): void {
                            $levels->select('sp_dl.id')
                                ->from('department_levels as sp_dl')
                                ->join('levels as sp_lvl', 'sp_lvl.id', '=', 'sp_dl.level_id')
                                ->where('sp_lvl.calendar_type', $type->value)
                                ->whereNull('sp_dl.deleted_at');
                        });
                });
            }
        });

        $query->where(function ($inner) use ($table): void {
            $inner->whereNull("{$table}.student_enrolment_status_id")
                ->orWhereNotIn("{$table}.student_enrolment_status_id", function (QueryBuilder $statuses): void {
                    $this->statusIdsWithSlugs($statuses, [...self::AWARD_SLUGS, ...self::DISQUALIFIED_SLUGS]);
                });
        });

        // A level already awarded on any phase is complete; there is nothing left to confirm.
        $query->whereNotExists(function (QueryBuilder $sub) use ($table): void {
            $sub->selectRaw('1')
                ->from('student_semesters as sp_award')
                ->whereColumn('sp_award.student_enrolment_id', "{$table}.id")
                ->whereNull('sp_award.deleted_at')
                ->whereIn('sp_award.student_enrolment_status_id', function (QueryBuilder $statuses): void {
                    $this->statusIdsWithSlugs($statuses, self::AWARD_SLUGS);
                });
        });

        $query->whereExists(function (QueryBuilder $sub) use ($table): void {
            $sub->selectRaw('1')
                ->from('department_level_courses as sp_dlc')
                ->join('programme_semesters as sp_ps', 'sp_ps.department_level_course_id', '=', 'sp_dlc.id')
                ->whereColumn('sp_dlc.department_level_id', "{$table}.department_level_id")
                ->whereColumn('sp_dlc.department_course_id', "{$table}.department_course_id")
                ->whereNull('sp_ps.deleted_at');
        });

        $allPeriodIds = array_values(array_unique(array_merge(...array_column($groups, 0))));

        // A mode change can leave two enrolments on one application in the same year; only the newest counts.
        $query->whereNotExists(function (QueryBuilder $sub) use ($allPeriodIds, $table): void {
            $sub->selectRaw('1')
                ->from('student_enrolments as sp_newer')
                ->whereColumn('sp_newer.student_application_id', "{$table}.student_application_id")
                ->whereColumn('sp_newer.id', '>', "{$table}.id")
                ->whereNull('sp_newer.deleted_at')
                ->whereIn('sp_newer.academic_calendar_id', $allPeriodIds);
        });

        return $query;
    }

    /**
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function whereState(BuilderContract $query, StudyPositionStateEnum $state, string $table = 'student_enrolments'): BuilderContract
    {
        $confirmations = function (QueryBuilder $sub) use ($table): QueryBuilder {
            return $sub->selectRaw('1')
                ->from('student_study_position_confirmations as sp_conf')
                ->whereColumn('sp_conf.student_enrolment_id', "{$table}.id")
                ->whereIn('sp_conf.academic_calendar_id', $this->currentPeriodIdsOrNone());
        };

        return match ($state) {
            StudyPositionStateEnum::UNCONFIRMED => $query->whereNotExists(
                fn (QueryBuilder $sub) => $confirmations($sub),
            ),
            StudyPositionStateEnum::FOLLOW_UP => $query->whereExists(
                fn (QueryBuilder $sub) => $confirmations($sub)
                    ->where('sp_conf.answer', '!=', StudyPositionAnswerEnum::PHASE->value),
            ),
            StudyPositionStateEnum::NEEDS_REVIEW => $query->whereExists(
                fn (QueryBuilder $sub) => $confirmations($sub)
                    ->where('sp_conf.answer', StudyPositionAnswerEnum::PHASE->value)
                    ->where('sp_conf.sync_status', StudyPositionSyncStatusEnum::NEEDS_REVIEW->value),
            ),
            StudyPositionStateEnum::CONFIRMED => $query->whereExists(
                fn (QueryBuilder $sub) => $confirmations($sub)
                    ->where('sp_conf.answer', StudyPositionAnswerEnum::PHASE->value)
                    ->where('sp_conf.sync_status', '!=', StudyPositionSyncStatusEnum::NEEDS_REVIEW->value),
            ),
        };
    }

    /**
     * Anything short of a settled confirmation.
     *
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function whereNeedsAttention(BuilderContract $query, string $table = 'student_enrolments'): BuilderContract
    {
        return $query->whereNotExists(function (QueryBuilder $sub) use ($table): void {
            $sub->selectRaw('1')
                ->from('student_study_position_confirmations as sp_conf')
                ->whereColumn('sp_conf.student_enrolment_id', "{$table}.id")
                ->whereIn('sp_conf.academic_calendar_id', $this->currentPeriodIdsOrNone())
                ->where('sp_conf.answer', StudyPositionAnswerEnum::PHASE->value)
                ->where('sp_conf.sync_status', '!=', StudyPositionSyncStatusEnum::NEEDS_REVIEW->value);
        });
    }

    /**
     * Left-joins this period's confirmation for grouped reporting; pair with stateCaseSql().
     *
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function leftJoinConfirmation(BuilderContract $query, string $table = 'student_enrolments', string $alias = 'sp_state'): BuilderContract
    {
        $periodIds = $this->currentPeriodIdsOrNone();

        return $query->leftJoin(
            "student_study_position_confirmations as {$alias}",
            function ($join) use ($alias, $table, $periodIds): void {
                $join->on("{$alias}.student_enrolment_id", '=', "{$table}.id")
                    ->whereIn("{$alias}.academic_calendar_id", $periodIds);
            },
        );
    }

    public function stateCaseSql(string $alias = 'sp_state'): string
    {
        $phase = StudyPositionAnswerEnum::PHASE->value;
        $review = StudyPositionSyncStatusEnum::NEEDS_REVIEW->value;

        return "CASE WHEN {$alias}.id IS NULL THEN '".StudyPositionStateEnum::UNCONFIRMED->value."'"
            ." WHEN {$alias}.answer <> '{$phase}' THEN '".StudyPositionStateEnum::FOLLOW_UP->value."'"
            ." WHEN {$alias}.sync_status = '{$review}' THEN '".StudyPositionStateEnum::NEEDS_REVIEW->value."'"
            ." ELSE '".StudyPositionStateEnum::CONFIRMED->value."' END";
    }

    /**
     * @return list<int>
     */
    public function currentPeriodIds(): array
    {
        return $this->periods->currentPeriodIds();
    }

    public function fingerprint(): string
    {
        return $this->periods->fingerprint();
    }

    /**
     * Label for reporting headers: one period when every type agrees on the year, otherwise all of them.
     */
    public function periodLabel(): ?string
    {
        $labels = array_values(array_unique(array_map(
            static fn (CurrentStudyPeriod $period): string => $period->label,
            $this->periods->all(),
        )));

        return $labels === [] ? null : implode(' / ', $labels);
    }

    /**
     * @return list<int>
     */
    private function currentPeriodIdsOrNone(): array
    {
        $ids = $this->currentPeriodIds();

        return $ids === [] ? [0] : $ids;
    }

    /**
     * @return list<array{0: list<int>, 1: AcademicCalendarTypeEnum}>
     */
    private function periodGroups(): array
    {
        $groups = [];

        foreach ($this->periods->all() as $period) {
            if ($period->yearPeriodIds !== []) {
                $groups[] = [$period->yearPeriodIds, $period->type];
            }
        }

        return $groups;
    }

    /**
     * @param  list<string>  $slugs
     */
    private function statusIdsWithSlugs(QueryBuilder $query, array $slugs): void
    {
        $query->select('sp_status.id')
            ->from('student_enrolment_statuses as sp_status')
            ->whereIn('sp_status.slug', $slugs);
    }
}
