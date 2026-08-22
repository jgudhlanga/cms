<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SyncStudentSemestersForEnrolmentService
{
    public function __construct(
        protected StudentSemesterPhaseResolver $phaseResolver,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    /**
     * @param  array{
     *     sourceSemesterId?: int|null,
     *     sourceStatusId?: int|null,
     *     sourceSyllabusIds?: list<int>|null,
     *     snapshotEnrolment?: bool
     * }  $options
     * @return list<StudentSemester>
     */
    public function sync(StudentEnrolment $enrolment, ?CarbonInterface $asOf = null, array $options = []): array
    {
        $enrolment->loadMissing(['academicCalendar', 'departmentLevel.level', 'semester', 'studentEnrolmentStatus']);

        $sourceSemesterId = isset($options['sourceSemesterId'])
            ? ($options['sourceSemesterId'] !== null ? (int) $options['sourceSemesterId'] : null)
            : ($enrolment->semester_id !== null ? (int) $enrolment->semester_id : null);

        $phases = $this->phaseResolver->phasesToCreateForEnrolment($enrolment, $asOf, $sourceSemesterId);

        if ($phases === []) {
            return [];
        }

        $sourceSemesterId = $options['sourceSemesterId'] ?? $enrolment->semester_id;
        $sourceStatusId = $options['sourceStatusId'] ?? $enrolment->student_enrolment_status_id;
        $sourceSyllabusIds = $options['sourceSyllabusIds'] ?? ($enrolment->course_syllabus_ids ?? []);
        $snapshotEnrolment = $options['snapshotEnrolment'] ?? true;

        $activeStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_ACTIVE);
        $proceedStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_PROCEED);
        $awardStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_AWARD);

        $sourceStatusSlug = null;

        if ($sourceStatusId !== null) {
            $enrolment->loadMissing('studentEnrolmentStatus');
            $sourceStatusSlug = $enrolment->studentEnrolmentStatus?->slug;
        }

        $latestPhase = end($phases);
        $created = [];

        return DB::transaction(function () use (
            $enrolment,
            $phases,
            $sourceSemesterId,
            $sourceStatusId,
            $sourceSyllabusIds,
            $snapshotEnrolment,
            $activeStatusId,
            $proceedStatusId,
            $awardStatusId,
            $sourceStatusSlug,
            $latestPhase,
            &$created,
        ): array {
            foreach ($phases as $phase) {
                $isLatest = $latestPhase instanceof Semester && (int) $phase->id === (int) $latestPhase->id;
                $matchesSource = $sourceSemesterId !== null && (int) $phase->id === (int) $sourceSemesterId;

                $statusId = $this->resolvePhaseStatusId(
                    $isLatest,
                    $matchesSource,
                    $sourceStatusId,
                    $sourceStatusSlug,
                    $activeStatusId,
                    $proceedStatusId,
                    $awardStatusId,
                );

                $syllabusIds = $matchesSource
                    ? array_values(array_filter(array_map('intval', $sourceSyllabusIds ?? [])))
                    : [];

                $studentSemester = StudentSemester::query()->updateOrCreate(
                    [
                        'student_enrolment_id' => $enrolment->id,
                        'semester_id' => $phase->id,
                    ],
                    [
                        'student_enrolment_status_id' => $statusId,
                        'course_syllabus_ids' => $syllabusIds !== [] ? $syllabusIds : null,
                    ],
                );

                $created[] = $studentSemester;
            }

            if ($snapshotEnrolment) {
                $this->snapshotLatestPhaseOntoEnrolment($enrolment, $created);
            }

            return $created;
        });
    }

    public function snapshotLatestPhaseOntoEnrolment(StudentEnrolment $enrolment, ?array $semesters = null): void
    {
        $semesters ??= $enrolment->studentSemesters()
            ->with('semester')
            ->get()
            ->sortBy(fn (StudentSemester $row): int => $this->phaseResolver->phaseOrdinal((string) ($row->semester?->slug ?? '')))
            ->values()
            ->all();

        if ($semesters === []) {
            return;
        }

        /** @var StudentSemester $latest */
        $latest = end($semesters);

        StudentEnrolment::withoutEvents(function () use ($enrolment, $latest): void {
            $enrolment->update([
                'semester_id' => $latest->semester_id,
                'student_enrolment_status_id' => $latest->student_enrolment_status_id,
                'course_syllabus_ids' => $latest->course_syllabus_ids,
            ]);
        });
    }

    private function resolvePhaseStatusId(
        bool $isLatest,
        bool $matchesSource,
        ?int $sourceStatusId,
        ?string $sourceStatusSlug,
        ?int $activeStatusId,
        ?int $proceedStatusId,
        ?int $awardStatusId,
    ): int {
        if ($isLatest) {
            if ($matchesSource && $sourceStatusId !== null) {
                return $sourceStatusId;
            }

            return $activeStatusId ?? $sourceStatusId ?? 0;
        }

        if ($sourceStatusSlug === StudentEnrolmentProgressionService::STATUS_AWARD && $awardStatusId !== null) {
            return $awardStatusId;
        }

        return $proceedStatusId ?? $activeStatusId ?? $sourceStatusId ?? 0;
    }
}
