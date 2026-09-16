<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\Students\StudyPositionStateEnum;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Support\Institution\ProgrammeSemesterNameFormatter;

/**
 * Per-student study position summaries for bulk reporting (the student list export).
 */
class StudyPositionReportService
{
    public function __construct(
        private readonly StudyPositionScope $scope,
        private readonly CurrentStudyPeriodResolver $periods,
    ) {}

    /**
     * One summary per student: the programme in the worst state represents them.
     *
     * @param  list<int>  $studentIds
     * @return array<int, array{period: string, state: string, confirmedPhase: string|null, phaseOnRecord: string|null, source: string|null, confirmedAt: string|null, note: string|null}>
     */
    public function summariesForStudentIds(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $periodIds = $this->scope->currentPeriodIds();

        $enrolments = $this->scope
            ->constrain(StudentEnrolment::query()->whereIn('student_enrolments.student_id', $studentIds))
            ->with([
                'departmentLevel.level',
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'studyPositionConfirmations' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->forPeriods($periodIds)
                    ->with('programmeSemester'),
            ])
            ->get();

        $summaries = [];
        $severities = [];

        foreach ($enrolments as $enrolment) {
            $period = $this->periods->forEnrolment($enrolment);

            if ($period === null) {
                continue;
            }

            /** @var StudentStudyPositionConfirmation|null $confirmation */
            $confirmation = $enrolment->studyPositionConfirmations
                ->first(fn (StudentStudyPositionConfirmation $row): bool => (int) $row->academic_calendar_id === $period->periodId());
            $state = StudyPositionStateEnum::fromConfirmation($confirmation);
            $studentId = (int) $enrolment->student_id;

            if (isset($severities[$studentId]) && $severities[$studentId] >= $state->severity()) {
                continue;
            }

            $levelName = $enrolment->departmentLevel?->level?->name;
            $recordPhase = $enrolment->currentStudentSemester()?->programmeSemester?->name;

            $severities[$studentId] = $state->severity();
            $summaries[$studentId] = [
                'period' => $period->label,
                'state' => $state->label(),
                'confirmedPhase' => $confirmation?->programmeSemester !== null
                    ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $confirmation->programmeSemester->name)
                    : $confirmation?->answer->label(),
                'phaseOnRecord' => $recordPhase !== null
                    ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $recordPhase)
                    : null,
                'source' => $confirmation?->source->label(),
                'confirmedAt' => $confirmation?->confirmed_at?->format('Y-m-d H:i'),
                'note' => $confirmation?->sync_note,
            ];
        }

        return $summaries;
    }
}
