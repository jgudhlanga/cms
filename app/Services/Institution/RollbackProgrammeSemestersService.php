<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Facades\DB;

class RollbackProgrammeSemestersService
{
    /**
     * @return array{
     *     dlcs: int,
     *     inclusions: int,
     *     configs: int,
     *     modules: int,
     *     pivots: int,
     *     deleted_programme_semesters: int
     * }
     */
    public function run(bool $dryRun = false): array
    {
        $dlcRows = DB::table('programme_structure_rollback_dlcs')->get();
        $inclusionRows = DB::table('programme_semester_rollback_inclusions')->get();
        $configRows = DB::table('programme_semester_rollback_class_configs')->get();
        $moduleRows = DB::table('programme_semester_rollback_modules')->get();
        $pivotRows = DB::table('programme_semester_rollback_class_pivots')->get();

        if ($dryRun) {
            return [
                'dlcs' => $dlcRows->count(),
                'inclusions' => $inclusionRows->count(),
                'configs' => $configRows->count(),
                'modules' => $moduleRows->count(),
                'pivots' => $pivotRows->count(),
                'deleted_programme_semesters' => ProgrammeSemester::query()->count(),
            ];
        }

        return DB::transaction(function () use ($dlcRows, $inclusionRows, $configRows, $moduleRows, $pivotRows): array {
            $counts = [
                'dlcs' => 0,
                'inclusions' => 0,
                'configs' => 0,
                'modules' => 0,
                'pivots' => 0,
                'deleted_programme_semesters' => 0,
            ];

            foreach ($pivotRows as $row) {
                AcademicCalendarStudentEnrolment::query()
                    ->whereKey($row->academic_calendar_student_enrolment_id)
                    ->update([
                        'academic_calendar_class_id' => $row->academic_calendar_class_id,
                        'student_semesters_id' => $row->student_semesters_id,
                        'is_live' => $row->is_live ?? true,
                        'concluded_at' => $row->concluded_at,
                    ]);
                $counts['pivots']++;
            }

            foreach ($inclusionRows as $row) {
                if ((bool) $row->was_created_by_backfill) {
                    StudentSemester::query()->whereKey($row->student_semester_id)->forceDelete();

                    continue;
                }

                StudentSemester::query()
                    ->whereKey($row->student_semester_id)
                    ->update([
                        'semester_id' => $row->semester_id,
                        'programme_semester_id' => null,
                        'student_enrolment_status_id' => $row->student_enrolment_status_id,
                        'course_syllabus_ids' => $row->course_syllabus_ids !== null
                            ? json_decode((string) $row->course_syllabus_ids, true)
                            : null,
                    ]);
                $counts['inclusions']++;
            }

            foreach ($configRows as $row) {
                if ((bool) $row->was_created_by_backfill) {
                    DB::table('class_configs')->where('id', $row->class_config_id)->delete();

                    continue;
                }

                DB::table('class_configs')
                    ->where('id', $row->class_config_id)
                    ->update([
                        'semester_id' => $row->semester_id,
                        'programme_semester_id' => null,
                        'name' => null,
                        'kind' => 'standard',
                        'slug' => 'standard',
                    ]);
                $counts['configs']++;
            }

            foreach ($moduleRows as $row) {
                CourseSyllabusModule::query()
                    ->whereKey($row->course_syllabus_module_id)
                    ->update([
                        'semester_id' => $row->semester_id,
                        'programme_semester_id' => null,
                    ]);
                $counts['modules']++;
            }

            foreach ($dlcRows as $row) {
                DepartmentLevelCourse::query()
                    ->whereKey($row->department_level_course_id)
                    ->update([
                        'duration_years' => $row->duration_years ?? 1,
                        'taught_semester_count' => $row->taught_semester_count ?? 2,
                        'includes_industrial_attachment' => (bool) ($row->includes_industrial_attachment ?? false),
                        'attachment_semester_count' => $row->attachment_semester_count ?? 0,
                    ]);
                $counts['dlcs']++;
            }

            $counts['deleted_programme_semesters'] = ProgrammeSemester::query()->count();
            ProgrammeSemester::query()->forceDelete();

            DB::table('programme_semester_progression_run_items')->truncate();
            DB::table('programme_semester_progression_runs')->truncate();
            DB::table('programme_semester_rollback_class_pivots')->truncate();
            DB::table('programme_semester_rollback_modules')->truncate();
            DB::table('programme_semester_rollback_class_configs')->truncate();
            DB::table('programme_semester_rollback_inclusions')->truncate();
            DB::table('programme_structure_rollback_dlcs')->truncate();

            return $counts;
        });
    }
}
