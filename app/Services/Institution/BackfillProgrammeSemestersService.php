<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentSemester;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Facades\DB;

class BackfillProgrammeSemestersService
{
    public function __construct(
        protected SyncProgrammeSemestersForOfferingAction $syncProgrammeSemesters,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * @return array{
     *     dlcs: int,
     *     programme_semesters: int,
     *     inclusions: int,
     *     configs: int,
     *     modules: int,
     *     pivots: int
     * }
     */
    public function run(bool $dryRun = false, bool $fresh = false): array
    {
        $counts = [
            'dlcs' => 0,
            'programme_semesters' => 0,
            'inclusions' => 0,
            'configs' => 0,
            'modules' => 0,
            'pivots' => 0,
        ];

        if ($fresh && ! $dryRun && DB::table('programme_structure_rollback_dlcs')->exists()) {
            throw new \RuntimeException('Cannot use --fresh when rollback snapshots already exist.');
        }

        $dlcs = DepartmentLevelCourse::query()
            ->with(['departmentLevel.level', 'programmeSemesters'])
            ->get();

        foreach ($dlcs as $dlc) {
            if ($dryRun) {
                $counts['dlcs']++;

                continue;
            }

            $this->snapshotDlc($dlc, $fresh);
            $this->applyDlcDefaults($dlc);
            $synced = $this->syncProgrammeSemesters->execute($dlc->fresh() ?? $dlc);
            $counts['dlcs']++;
            $counts['programme_semesters'] += $synced->count();
        }

        if ($dryRun) {
            $counts['inclusions'] = StudentSemester::query()->whereNull('programme_semester_id')->count();
            $counts['configs'] = ClassConfig::query()->whereNull('programme_semester_id')->count();
            $counts['modules'] = CourseSyllabusModule::query()->whereNull('programme_semester_id')->count();
            $counts['pivots'] = AcademicCalendarStudentEnrolment::query()
                ->whereNull('is_live')
                ->orWhereNull('concluded_at')
                ->count();

            return $counts;
        }

        foreach (StudentSemester::query()->with(['enrolment', 'semester'])->cursor() as $inclusion) {
            $this->snapshotInclusion($inclusion);
            $programmeSemesterId = $this->resolveInclusionProgrammeSemesterId($inclusion);

            if ($programmeSemesterId === null) {
                continue;
            }

            $inclusion->update(['programme_semester_id' => $programmeSemesterId]);
            $counts['inclusions']++;
        }

        foreach (ClassConfig::query()->with('semester')->cursor() as $config) {
            $this->snapshotClassConfig($config);
            $programmeSemesterId = $this->resolveConfigProgrammeSemesterId($config);

            $updates = [
                'slug' => $config->slug ?? 'standard',
                'kind' => $config->kind ?? 'standard',
            ];

            if ($programmeSemesterId !== null) {
                $updates['programme_semester_id'] = $programmeSemesterId;
            }

            if ($config->name === null && $programmeSemesterId !== null) {
                $name = ProgrammeSemester::query()->whereKey($programmeSemesterId)->value('name');
                $updates['name'] = is_string($name) ? $name : null;
            }

            $config->update($updates);
            $counts['configs']++;
        }

        foreach (CourseSyllabusModule::query()->with('courseSyllabus.departmentLevelCourse')->cursor() as $module) {
            $this->snapshotModule($module);
            $programmeSemesterId = $this->resolveModuleProgrammeSemesterId($module);

            if ($programmeSemesterId === null) {
                continue;
            }

            $module->update(['programme_semester_id' => $programmeSemesterId]);
            $counts['modules']++;
        }

        foreach (AcademicCalendarStudentEnrolment::query()->cursor() as $pivot) {
            $this->snapshotPivot($pivot);

            if ($pivot->is_live === null) {
                $pivot->update(['is_live' => true]);
                $counts['pivots']++;
            }
        }

        return $counts;
    }

    private function snapshotDlc(DepartmentLevelCourse $dlc, bool $fresh): void
    {
        if (! $fresh && DB::table('programme_structure_rollback_dlcs')->where('department_level_course_id', $dlc->id)->exists()) {
            return;
        }

        DB::table('programme_structure_rollback_dlcs')->updateOrInsert(
            ['department_level_course_id' => $dlc->id],
            [
                'duration_years' => $dlc->duration_years,
                'taught_semester_count' => $dlc->taught_semester_count,
                'includes_industrial_attachment' => $dlc->includes_industrial_attachment,
                'attachment_semester_count' => $dlc->attachment_semester_count,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function applyDlcDefaults(DepartmentLevelCourse $dlc): void
    {
        $dlc->loadMissing('departmentLevel.level');
        $calendarType = $dlc->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        $defaultTaught = ProgrammeSemesterNameFormatter::periodsPerYear($calendarType);

        $dlc->update([
            'duration_years' => $dlc->duration_years ?? 1,
            'taught_semester_count' => $dlc->taught_semester_count ?? $defaultTaught,
            'includes_industrial_attachment' => $dlc->includes_industrial_attachment ?? false,
            'attachment_semester_count' => $dlc->attachment_semester_count ?? 0,
        ]);
    }

    private function snapshotInclusion(StudentSemester $inclusion): void
    {
        DB::table('programme_semester_rollback_inclusions')->updateOrInsert(
            ['student_semester_id' => $inclusion->id],
            [
                'semester_id' => $inclusion->semester_id,
                'programme_semester_id' => $inclusion->programme_semester_id,
                'student_enrolment_status_id' => $inclusion->student_enrolment_status_id,
                'course_syllabus_ids' => json_encode($inclusion->course_syllabus_ids),
                'was_created_by_backfill' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function snapshotClassConfig(ClassConfig $config): void
    {
        DB::table('programme_semester_rollback_class_configs')->updateOrInsert(
            ['class_config_id' => $config->id],
            [
                'semester_id' => $config->semester_id,
                'programme_semester_id' => $config->programme_semester_id,
                'name' => $config->name,
                'kind' => $config->kind,
                'slug' => $config->slug,
                'was_created_by_backfill' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function snapshotModule(CourseSyllabusModule $module): void
    {
        DB::table('programme_semester_rollback_modules')->updateOrInsert(
            ['course_syllabus_module_id' => $module->id],
            [
                'semester_id' => $module->semester_id,
                'programme_semester_id' => $module->programme_semester_id,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function snapshotPivot(AcademicCalendarStudentEnrolment $pivot): void
    {
        DB::table('programme_semester_rollback_class_pivots')->updateOrInsert(
            ['academic_calendar_student_enrolment_id' => $pivot->id],
            [
                'academic_calendar_class_id' => $pivot->academic_calendar_class_id,
                'student_semesters_id' => $pivot->student_semesters_id,
                'is_live' => $pivot->is_live,
                'concluded_at' => $pivot->concluded_at,
                'was_created_by_backfill' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function resolveInclusionProgrammeSemesterId(StudentSemester $inclusion): ?int
    {
        if ($inclusion->programme_semester_id !== null) {
            return (int) $inclusion->programme_semester_id;
        }

        $enrolment = $inclusion->enrolment;

        if ($enrolment === null || $inclusion->semester_id === null) {
            return null;
        }

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return null;
        }

        $programmeSemester = $this->programmeSemesterResolver->mapGlobalSemesterToProgrammeSemester(
            $dlc,
            (int) $inclusion->semester_id,
        );

        return $programmeSemester?->id !== null ? (int) $programmeSemester->id : null;
    }

    private function resolveConfigProgrammeSemesterId(ClassConfig $config): ?int
    {
        if ($config->programme_semester_id !== null) {
            return (int) $config->programme_semester_id;
        }

        if ($config->semester_id === null) {
            return null;
        }

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourseForOffering(
            (int) $config->department_course_id,
            (int) $config->department_level_id,
        );

        if ($dlc === null) {
            return null;
        }

        $programmeSemester = $this->programmeSemesterResolver->mapGlobalSemesterToProgrammeSemester(
            $dlc,
            (int) $config->semester_id,
        );

        return $programmeSemester?->id !== null ? (int) $programmeSemester->id : null;
    }

    private function resolveModuleProgrammeSemesterId(CourseSyllabusModule $module): ?int
    {
        if ($module->programme_semester_id !== null) {
            return (int) $module->programme_semester_id;
        }

        if ($module->semester_id === null) {
            return null;
        }

        $dlc = $module->courseSyllabus?->departmentLevelCourse;

        if ($dlc === null) {
            return null;
        }

        $programmeSemester = $this->programmeSemesterResolver->mapGlobalSemesterToProgrammeSemester(
            $dlc,
            (int) $module->semester_id,
        );

        return $programmeSemester?->id !== null ? (int) $programmeSemester->id : null;
    }
}
