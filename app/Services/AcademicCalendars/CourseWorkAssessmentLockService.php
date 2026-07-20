<?php

namespace App\Services\AcademicCalendars;

use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CourseWorkAssessmentLockService
{
    /**
     * @param  Collection<int, CourseSyllabusModule>|list<CourseSyllabusModule>  $modules
     * @return array<int, array{
     *     moduleId: int,
     *     hasEditableCourseWork: bool,
     *     allAssessmentTypesLocked: bool,
     *     lockedAssessmentTypeIds: list<int>,
     *     lockedAssessmentTypeNames: list<string>,
     *     readOnlyMessage: string|null
     * }>
     */
    public function locksForClassAndModules(AcademicCalendarClass $class, Collection|array $modules): array
    {
        $class->loadMissing('classConfig');
        $classConfig = $class->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            return [];
        }

        $modulesCollection = $modules instanceof Collection ? $modules : collect($modules);
        $lockedAssessmentTypes = $this->lockedAssessmentTypesForClassConfig($classConfig);

        return $modulesCollection
            ->mapWithKeys(fn (CourseSyllabusModule $module): array => [
                (int) $module->id => $this->lockPayloadForModule($module, $lockedAssessmentTypes),
            ])
            ->all();
    }

    public function assertMutationAllowed(
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        ?int $assessmentTypeId,
    ): void {
        if ($module->capture_mark_only) {
            return;
        }

        if ($assessmentTypeId === null) {
            return;
        }

        $lockedAssessmentTypes = $this->lockedAssessmentTypesForClassConfig($classConfig);
        $locked = $lockedAssessmentTypes[$assessmentTypeId] ?? null;

        if ($locked === null) {
            return;
        }

        throw ValidationException::withMessages([
            'assessmentTypeId' => [
                __('academic_calendar.course_work_assessment_locked', [
                    'assessment' => $locked['name'],
                    'end_date' => $locked['endDate'],
                ]),
            ],
        ]);
    }

    /**
     * @return array<int, array{name: string, endDate: string}>
     */
    private function lockedAssessmentTypesForClassConfig(ClassConfig $classConfig): array
    {
        $academicCalendar = Helper::resolveAcademicCalendar();
        $modeOfStudyId = (int) $classConfig->mode_of_study_id;

        if ($modeOfStudyId < 1) {
            return [];
        }

        $today = now()->startOfDay();

        $latestEndDates = AssessmentCalendar::query()
            ->where('academic_calendar_id', (int) $academicCalendar->id)
            ->with('assessmentType')
            ->get()
            ->reduce(function (array $carry, AssessmentCalendar $calendar) use ($modeOfStudyId, $today): array {
                $assessmentType = $calendar->assessmentType;

                if (! $assessmentType instanceof AssessmentType) {
                    return $carry;
                }

                $typeId = (int) $assessmentType->id;
                $modeIds = array_values(array_filter(
                    array_map('intval', $assessmentType->modes_of_study ?? []),
                    static fn (int $id): bool => $id > 0,
                ));

                if (! in_array($modeOfStudyId, $modeIds, true)) {
                    return $carry;
                }

                $endDate = $calendar->end_date;

                if (! $endDate instanceof CarbonInterface) {
                    return $carry;
                }

                $formattedEndDate = $endDate->format('Y-m-d');

                if (! isset($carry[$typeId]) || strcmp($formattedEndDate, $carry[$typeId]['endDate']) > 0) {
                    $carry[$typeId] = [
                        'name' => (string) $assessmentType->name,
                        'endDate' => $formattedEndDate,
                    ];
                }

                return $carry;
            }, []);

        return array_filter(
            $latestEndDates,
            fn (array $locked) => $today->gt(Carbon::parse($locked['endDate'])->endOfDay()),
        );
    }

    /**
     * @param  array<int, array{name: string, endDate: string}>  $lockedAssessmentTypes
     * @return array{
     *     moduleId: int,
     *     hasEditableCourseWork: bool,
     *     allAssessmentTypesLocked: bool,
     *     lockedAssessmentTypeIds: list<int>,
     *     lockedAssessmentTypeNames: list<string>,
     *     readOnlyMessage: string|null
     * }
     */
    private function lockPayloadForModule(CourseSyllabusModule $module, array $lockedAssessmentTypes): array
    {
        $lockedAssessmentTypeIds = array_values(array_keys($lockedAssessmentTypes));
        $lockedAssessmentTypeNames = array_values(array_map(
            static fn (array $locked): string => $locked['name'],
            $lockedAssessmentTypes,
        ));

        if ($module->capture_mark_only) {
            return [
                'moduleId' => (int) $module->id,
                'hasEditableCourseWork' => true,
                'allAssessmentTypesLocked' => false,
                'lockedAssessmentTypeIds' => [],
                'lockedAssessmentTypeNames' => [],
                'readOnlyMessage' => null,
            ];
        }

        $allAssessmentTypesLocked = $lockedAssessmentTypeIds !== [];

        return [
            'moduleId' => (int) $module->id,
            'hasEditableCourseWork' => ! $allAssessmentTypesLocked,
            'allAssessmentTypesLocked' => $allAssessmentTypesLocked,
            'lockedAssessmentTypeIds' => $lockedAssessmentTypeIds,
            'lockedAssessmentTypeNames' => $lockedAssessmentTypeNames,
            'readOnlyMessage' => $allAssessmentTypesLocked
                ? __('academic_calendar.course_work_assessment_read_only_notice', [
                    'assessments' => implode(', ', $lockedAssessmentTypeNames),
                ])
                : null,
        ];
    }
}
