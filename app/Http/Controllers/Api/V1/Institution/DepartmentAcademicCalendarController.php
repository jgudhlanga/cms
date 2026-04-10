<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\Shared\ClassListTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepartmentAcademicCalendarController extends Controller
{
    public function __construct() {}

    public function departmentAcademicCalendar(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $institutionDepartment = InstitutionDepartment::with([
            'departmentCourses.course',
            'departmentCourses.departmentCourseLevels.departmentLevel' => function ($query) {
                $query->withTrashed();
            },
            'departmentCourses.departmentCourseLevels.departmentLevel.level' => function ($query) {
                $query->withTrashed();
            },
        ])->findOrFail($institutionDepartment->id);

        $academicCalendarId = request()->query('academic_calendar');
        $modeOfStudyId = request()->query('mode_of_study_id');
        $classConfigLookup = [];
        $totalnClassLookup = [];
        $totalFinalListLookup = [];
        if ($academicCalendarId && $modeOfStudyId) {
            $configs = ClassConfig::where('academic_calendar_id', $academicCalendarId)
                ->where('institution_department_id', $institutionDepartment->id)
                ->where('mode_of_study_id', $modeOfStudyId)
                ->get();
            foreach ($configs as $config) {
                $key = "{$config->department_course_id}_{$config->department_level_id}";
                $classConfigLookup[$key] = [
                    'students_per_class' => $config->students_per_class ?? 0,
                    'id' => $config->id,
                ];
            }

            $finalClassCounts = ClassConfig::query()
                ->leftJoin('academic_calandar_classes', function ($join) {
                    $join->on('academic_calandar_classes.class_config_id', '=', 'class_configs.id')
                        ->whereNull('academic_calandar_classes.deleted_at');
                })
                ->leftJoin('academic_calendar_student_programs', function ($join) {
                    $join->on('academic_calendar_student_programs.academic_calendar_class_id', '=', 'academic_calandar_classes.id')
                        ->whereNull('academic_calendar_student_programs.deleted_at');
                })
                ->where('class_configs.academic_calendar_id', $academicCalendarId)
                ->where('class_configs.institution_department_id', $institutionDepartment->id)
                ->where('class_configs.mode_of_study_id', $modeOfStudyId)
                ->select([
                    'class_configs.department_course_id',
                    'class_configs.department_level_id',
                    DB::raw('COUNT(academic_calendar_student_programs.id) as total_final_class'),
                ])
                ->groupBy('class_configs.department_course_id', 'class_configs.department_level_id')
                ->get();

            foreach ($finalClassCounts as $countRow) {
                $key = "{$countRow->department_course_id}_{$countRow->department_level_id}";
                $totalnClassLookup[$key] = (int) $countRow->total_final_class;
            }

            $finalListCounts = StudentProgram::query()
                ->leftJoin('class_lists', function ($join) {
                    $join->on('class_lists.student_program_id', '=', 'student_programs.id')
                        ->where('class_lists.type', ClassListTypeEnum::FINAL->value)
                        ->whereNull('class_lists.deleted_at');
                })
                ->where('student_programs.institution_department_id', $institutionDepartment->id)
                ->where('student_programs.mode_of_study_id', $modeOfStudyId)
                ->whereNull('student_programs.deleted_at')
                ->select([
                    'student_programs.department_course_id',
                    'student_programs.department_level_id',
                    DB::raw('COUNT(class_lists.id) as total_final_list'),
                ])
                ->groupBy('student_programs.department_course_id', 'student_programs.department_level_id')
                ->get();

            foreach ($finalListCounts as $countRow) {
                $key = "{$countRow->department_course_id}_{$countRow->department_level_id}";
                $totalFinalListLookup[$key] = (int) $countRow->total_final_list;
            }
        }

        $grouped = $institutionDepartment->departmentCourses->map(function ($course) use ($institutionDepartment, $classConfigLookup, $totalnClassLookup, $totalFinalListLookup) {
            return [
                'institutionDepartmentId' => $institutionDepartment->id,
                'departmentCourseId' => (string) $course->id,
                'courseName' => $course->course->name,
                'levels' => $course->departmentCourseLevels
                    ->map(function ($levelCourse) use ($course, $classConfigLookup, $totalnClassLookup, $totalFinalListLookup) {
                        $departmentLevel = $levelCourse->departmentLevel;
                        if ($departmentLevel === null) {
                            return null;
                        }

                        $level = $departmentLevel->level;
                        if ($level === null) {
                            return null;
                        }

                        $key = "{$course->id}_{$levelCourse->department_level_id}";
                        $configData = $classConfigLookup[$key] ?? null;

                        return [
                            'departmentLevelId' => (string) $departmentLevel->id,
                            'levelName' => $level->name,
                            'studentsPerClass' => $configData['students_per_class'] ?? 0,
                            'classConfigId' => $configData['id'] ?? null,
                            'totalnClass' => $totalnClassLookup[$key] ?? 0,
                            'totalFinalList' => $totalFinalListLookup[$key] ?? 0,
                        ];
                    })
                    ->filter()
                    ->values(),
            ];
        })->values();

        return response()->json($grouped);
    }
}
