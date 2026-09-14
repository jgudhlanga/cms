<?php

namespace App\Services\AcademicCalendars;

use App\DTO\Assessments\EffectiveAssessmentWindow;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Assessments\EffectiveAssessmentWindowResolver;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Decides whether course work may be captured for a class/module/assessment type. It combines the
 * department course capture switch with the effective assessment window (global calendar, narrowed
 * by a department calendar, reopened by an approved extension) resolved for the class itself.
 */
class CourseWorkAssessmentLockService
{
    public function __construct(
        private readonly EffectiveAssessmentWindowResolver $windowResolver,
    ) {}

    /**
     * @param  Collection<int, CourseSyllabusModule>|list<CourseSyllabusModule>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function locksForClassAndModules(AcademicCalendarClass $class, Collection|array $modules): array
    {
        $class->loadMissing('classConfig');
        $classConfig = $class->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            return [];
        }

        return $this->buildLocks(
            $classConfig,
            $modules,
            $this->windowResolver->academicCalendarIdForClass((int) $class->id),
            (int) $class->id,
        );
    }

    /**
     * Lock state for a whole class config (department pages spanning several classes). Extensions are
     * granted per class, so they are not reflected here; saves are still checked per class.
     *
     * @param  Collection<int, CourseSyllabusModule>|list<CourseSyllabusModule>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function locksForClassConfigAndModules(ClassConfig $classConfig, Collection|array $modules): array
    {
        return $this->buildLocks(
            $classConfig,
            $modules,
            $this->windowResolver->academicCalendarIdForClassConfig((int) $classConfig->id),
            null,
        );
    }

    public function assertMutationAllowed(
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        ?int $assessmentTypeId,
        ?int $studentEnrolmentId = null,
        ?int $classId = null,
    ): void {
        // The department course capture switch applies to every module, mark-only modules included.
        if ($this->isCaptureDisabled($classConfig)) {
            throw ValidationException::withMessages([
                'courseworkCaptureEnabled' => [
                    __('academic_calendar.course_work_capture_disabled'),
                ],
            ]);
        }

        if (! $module->capture_mark_only && $assessmentTypeId === null) {
            return;
        }

        // Never decide a save from a cached answer: an extension may have been approved or revoked since.
        $this->windowResolver->flush();

        $academicCalendarId = match (true) {
            $studentEnrolmentId !== null => $this->windowResolver->academicCalendarIdForEnrolment($studentEnrolmentId),
            $classId !== null => $this->windowResolver->academicCalendarIdForClass($classId),
            default => $this->windowResolver->academicCalendarIdForClassConfig((int) $classConfig->id),
        };

        $window = $module->capture_mark_only
            ? $this->windowResolver->moduleMarkWindowFor(
                (int) $academicCalendarId,
                (int) $classConfig->institution_department_id,
                (int) $classConfig->mode_of_study_id,
                $classId,
                (int) $module->id,
            )
            : $this->windowResolver->windowFor(
                (int) $academicCalendarId,
                (int) $classConfig->institution_department_id,
                (int) $classConfig->mode_of_study_id,
                (int) $assessmentTypeId,
                $classId,
                (int) $module->id,
            );

        if ($window->isCaptureAllowed()) {
            return;
        }

        throw ValidationException::withMessages([
            ($module->capture_mark_only ? 'mark' : 'assessmentTypeId') => [$window->message()],
        ]);
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>|list<CourseSyllabusModule>  $modules
     * @return array<int, array<string, mixed>>
     */
    private function buildLocks(ClassConfig $classConfig, Collection|array $modules, ?int $academicCalendarId, ?int $classId): array
    {
        // Cache calendar lookups for this build only: this service can outlive a request (Octane, reused
        // controller instances) and calendars, department windows or extensions may change in between.
        $this->windowResolver->flush();
        $modulesCollection = $modules instanceof Collection ? $modules : collect($modules);
        $assessmentTypeNames = $this->assessmentTypeNamesForMode((int) $classConfig->mode_of_study_id);

        if ($this->isCaptureDisabled($classConfig)) {
            return $modulesCollection
                ->mapWithKeys(fn (CourseSyllabusModule $module): array => [
                    (int) $module->id => $this->captureDisabledPayload($module, $assessmentTypeNames),
                ])
                ->all();
        }

        return $modulesCollection
            ->mapWithKeys(fn (CourseSyllabusModule $module): array => [
                (int) $module->id => $this->lockPayloadForModule(
                    $classConfig,
                    $module,
                    (int) $academicCalendarId,
                    $classId,
                    $assessmentTypeNames,
                ),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $assessmentTypeNames
     * @return array<string, mixed>
     */
    private function lockPayloadForModule(
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        int $academicCalendarId,
        ?int $classId,
        array $assessmentTypeNames,
    ): array {
        $departmentId = (int) $classConfig->institution_department_id;
        $modeOfStudyId = (int) $classConfig->mode_of_study_id;

        if ($module->capture_mark_only) {
            $window = $this->windowResolver->moduleMarkWindowFor(
                $academicCalendarId,
                $departmentId,
                $modeOfStudyId,
                $classId,
                (int) $module->id,
            );
            $editable = $window->isCaptureAllowed();

            return [
                'moduleId' => (int) $module->id,
                'hasEditableCourseWork' => $editable,
                'allAssessmentTypesLocked' => ! $editable,
                'lockedAssessmentTypeIds' => [],
                'lockedAssessmentTypeNames' => [],
                'readOnlyMessage' => $editable ? null : $window->message(),
                'windows' => [$window->toArray()],
            ];
        }

        $windows = $this->windowResolver->windowsFor(
            $academicCalendarId,
            $departmentId,
            $modeOfStudyId,
            $classId,
            (int) $module->id,
        );

        foreach ($assessmentTypeNames as $assessmentTypeId => $assessmentTypeName) {
            $windows[$assessmentTypeId] ??= EffectiveAssessmentWindow::notConfigured($assessmentTypeId, $assessmentTypeName);
        }

        $locked = array_filter(
            $windows,
            static fn (EffectiveAssessmentWindow $window): bool => ! $window->isCaptureAllowed(),
        );
        $allAssessmentTypesLocked = $windows !== [] && count($locked) === count($windows);

        return [
            'moduleId' => (int) $module->id,
            'hasEditableCourseWork' => ! $allAssessmentTypesLocked,
            'allAssessmentTypesLocked' => $allAssessmentTypesLocked,
            'lockedAssessmentTypeIds' => array_values(array_map('intval', array_keys($locked))),
            'lockedAssessmentTypeNames' => array_values(array_map(
                static fn (EffectiveAssessmentWindow $window): string => $window->assessmentTypeName,
                $locked,
            )),
            'readOnlyMessage' => $locked === []
                ? null
                : implode(' ', array_map(
                    static fn (EffectiveAssessmentWindow $window): string => $window->message(),
                    array_values($locked),
                )),
            'windows' => array_values(array_map(
                static fn (EffectiveAssessmentWindow $window): array => $window->toArray(),
                $windows,
            )),
        ];
    }

    private function isCaptureDisabled(ClassConfig $classConfig): bool
    {
        $classConfig->loadMissing('departmentCourse');

        return $classConfig->departmentCourse?->coursework_capture_enabled === false;
    }

    /**
     * @return array<int, string>
     */
    private function assessmentTypeNamesForMode(int $modeOfStudyId): array
    {
        return AssessmentType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'modes_of_study'])
            ->filter(fn (AssessmentType $type): bool => in_array(
                $modeOfStudyId,
                array_map('intval', $type->modes_of_study ?? []),
                true,
            ))
            ->mapWithKeys(fn (AssessmentType $type): array => [(int) $type->id => (string) $type->name])
            ->all();
    }

    /**
     * @param  array<int, string>  $assessmentTypeNames
     * @return array<string, mixed>
     */
    private function captureDisabledPayload(CourseSyllabusModule $module, array $assessmentTypeNames): array
    {
        return [
            'moduleId' => (int) $module->id,
            'hasEditableCourseWork' => false,
            'allAssessmentTypesLocked' => true,
            'lockedAssessmentTypeIds' => array_values(array_keys($assessmentTypeNames)),
            'lockedAssessmentTypeNames' => array_values($assessmentTypeNames),
            'readOnlyMessage' => __('academic_calendar.course_work_capture_disabled'),
            'windows' => [],
        ];
    }
}
