<?php

namespace App\Services\AcademicCalendars;

use App\DTO\Assessments\EffectiveAssessmentWindow;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Assessments\EffectiveAssessmentWindowResolver;
use Illuminate\Support\Collection;

/**
 * Coursework capture progress for one programme (class config): expected, captured and missing marks for
 * every class, module and assessment type, alongside each assessment's capture window. Uses the same
 * module and staffing resolution as the department classes screens.
 */
class CourseWorkProgressService
{
    public function __construct(
        private readonly ClassStaffingService $classStaffingService,
        private readonly EffectiveAssessmentWindowResolver $windowResolver,
        private readonly LecturerInChargeService $lecturersInCharge,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forClassConfig(ClassConfig $classConfig): array
    {
        $classConfig->loadMissing(['departmentCourse.course', 'departmentLevel.level', 'modeOfStudy', 'institutionDepartment.department']);
        $this->windowResolver->flush();

        $modeOfStudyId = (int) $classConfig->mode_of_study_id;
        $institutionDepartmentId = (int) $classConfig->institution_department_id;
        $academicCalendarId = $this->windowResolver->academicCalendarIdForClassConfig((int) $classConfig->id);

        $semesterConfig = $this->classStaffingService->classConfigForStaffing($classConfig);
        $modules = $semesterConfig instanceof ClassConfig
            ? $this->classStaffingService->resolveSemesterModules($semesterConfig)->values()
            : collect();

        $assessmentTypes = AssessmentType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'modes_of_study'])
            ->filter(fn (AssessmentType $type): bool => in_array($modeOfStudyId, array_map('intval', $type->modes_of_study ?? []), true))
            ->values();

        $windows = $academicCalendarId !== null
            ? $this->windowResolver->windowsFor($academicCalendarId, $institutionDepartmentId, $modeOfStudyId)
            : [];

        $classes = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfig->id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $classIds = $classes->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $enrolmentIdsByClassId = $this->liveEnrolmentIdsByClassId($classIds);
        $capturedEnrolmentsByKey = $this->capturedEnrolmentsByModuleAndType($enrolmentIdsByClassId, $modules);
        $staffIdsByClassModule = $this->classStaffingService->classModuleStaffIdsByClassId($classIds, $modules);
        $staffNamesById = $this->classStaffingService->staffNamesKeyedById(collect($staffIdsByClassModule)->flatten()->all());

        $totals = ['expected' => 0, 'captured' => 0, 'missing' => 0];
        $classRows = [];

        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $enrolmentSet = array_flip($enrolmentIdsByClassId[$classId] ?? []);
            $studentCount = count($enrolmentSet);
            $moduleRows = [];

            foreach ($modules as $module) {
                $typeIds = $module->capture_mark_only
                    ? [0]
                    : $assessmentTypes->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $byAssessment = [];
                $moduleCaptured = 0;

                foreach ($typeIds as $typeId) {
                    $captured = count(array_filter(
                        $capturedEnrolmentsByKey[$module->id.'-'.$typeId] ?? [],
                        static fn (int $enrolmentId): bool => isset($enrolmentSet[$enrolmentId]),
                    ));
                    $moduleCaptured += $captured;
                    $byAssessment[] = [
                        'assessmentTypeId' => $typeId === 0 ? null : $typeId,
                        'expected' => $studentCount,
                        'captured' => $captured,
                        'missing' => max(0, $studentCount - $captured),
                    ];
                }

                $moduleExpected = $studentCount * count($typeIds);
                $totals['expected'] += $moduleExpected;
                $totals['captured'] += $moduleCaptured;

                $moduleRows[] = [
                    'id' => (int) $module->id,
                    'code' => (string) ($module->code ?? ''),
                    'title' => (string) $module->title,
                    'captureMarkOnly' => (bool) $module->capture_mark_only,
                    'lecturers' => $this->classStaffingService->orderedStaffNames(
                        $staffIdsByClassModule[$classId][(int) $module->id] ?? [],
                        $staffNamesById,
                    ),
                    'expected' => $moduleExpected,
                    'captured' => $moduleCaptured,
                    'missing' => max(0, $moduleExpected - $moduleCaptured),
                    'byAssessment' => $byAssessment,
                ];
            }

            $classRows[] = [
                'id' => $classId,
                'name' => (string) $class->name,
                'studentCount' => $studentCount,
                'modules' => $moduleRows,
            ];
        }

        $totals['missing'] = max(0, $totals['expected'] - $totals['captured']);
        $totals['percent'] = $totals['expected'] > 0 ? (int) round(($totals['captured'] / $totals['expected']) * 100) : null;

        return [
            'classConfigId' => (int) $classConfig->id,
            'institutionDepartmentId' => $institutionDepartmentId,
            'academicCalendarId' => $academicCalendarId,
            'calendarYear' => (string) $classConfig->calendar_year,
            'programme' => $this->programmeLabel($classConfig),
            'departmentName' => (string) ($classConfig->institutionDepartment?->department?->name ?? ''),
            'lecturerInCharge' => $this->lecturersInCharge->lecturerInChargeFor($classConfig),
            'assessments' => $assessmentTypes
                ->map(fn (AssessmentType $type): array => ($windows[(int) $type->id]
                    ?? EffectiveAssessmentWindow::notConfigured((int) $type->id, (string) $type->name))->toArray())
                ->values()
                ->all(),
            'totals' => $totals,
            'classes' => $classRows,
            'lastReport' => $this->lastReport($classConfig),
        ];
    }

    public function programmeLabel(ClassConfig $classConfig): string
    {
        $classConfig->loadMissing(['departmentCourse.course', 'departmentLevel.level', 'modeOfStudy']);

        $details = array_filter([
            $classConfig->departmentLevel?->level?->name,
            $classConfig->modeOfStudy?->name,
            $classConfig->calendar_year,
        ]);

        return trim(sprintf(
            '%s (%s)',
            (string) ($classConfig->departmentCourse?->course?->name ?? ''),
            implode(', ', $details),
        ));
    }

    /**
     * @param  list<int>  $classIds
     * @return array<int, list<int>>
     */
    private function liveEnrolmentIdsByClassId(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        return AcademicCalendarStudentEnrolment::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->where('is_live', true)->orWhereNull('is_live'))
            ->get(['academic_calendar_class_id', 'student_enrolment_id'])
            ->groupBy('academic_calendar_class_id')
            ->map(fn (Collection $rows): array => $rows->pluck('student_enrolment_id')->map(fn ($id): int => (int) $id)->unique()->values()->all())
            ->all();
    }

    /**
     * Enrolment ids with a captured (non-null) mark, keyed "moduleId-assessmentTypeId" (0 for mark-only).
     *
     * @param  array<int, list<int>>  $enrolmentIdsByClassId
     * @param  Collection<int, CourseSyllabusModule>  $modules
     * @return array<string, list<int>>
     */
    private function capturedEnrolmentsByModuleAndType(array $enrolmentIdsByClassId, Collection $modules): array
    {
        $enrolmentIds = array_values(array_unique(array_merge([], ...array_values($enrolmentIdsByClassId))));

        if ($enrolmentIds === [] || $modules->isEmpty()) {
            return [];
        }

        return CourseWorkMark::query()
            ->whereIn('student_enrolment_id', $enrolmentIds)
            ->whereIn('course_syllabus_module_id', $modules->pluck('id')->all())
            ->whereNotNull('mark')
            ->get(['student_enrolment_id', 'course_syllabus_module_id', 'assessment_type_id'])
            ->groupBy(fn (CourseWorkMark $mark): string => $mark->course_syllabus_module_id.'-'.((int) ($mark->assessment_type_id ?? 0)))
            ->map(fn (Collection $marks): array => $marks->pluck('student_enrolment_id')->map(fn ($id): int => (int) $id)->unique()->values()->all())
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastReport(ClassConfig $classConfig): ?array
    {
        $report = CourseWorkProgressReport::query()
            ->with(['submitter', 'acknowledger'])
            ->where('class_config_id', $classConfig->id)
            ->latest('submitted_at')
            ->first();

        if (! $report instanceof CourseWorkProgressReport) {
            return null;
        }

        return [
            'id' => (int) $report->id,
            'submittedAt' => $report->submitted_at?->toIso8601String(),
            'submitterName' => (string) ($report->submitter?->full_name ?? ''),
            'acknowledgedAt' => $report->acknowledged_at?->toIso8601String(),
            'acknowledgerName' => (string) ($report->acknowledger?->full_name ?? ''),
            'hodComment' => $report->hod_comment,
        ];
    }
}
