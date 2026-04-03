<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\JsonResponse;

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
        }

        $grouped = $institutionDepartment->departmentCourses->map(function ($course) use ($institutionDepartment, $classConfigLookup) {
            return [
                'institutionDepartmentId' => $institutionDepartment->id,
                'departmentCourseId' => (string) $course->id,
                'courseName' => $course->course->name,
                'levels' => $course->departmentCourseLevels
                    ->map(function ($levelCourse) use ($course, $classConfigLookup) {
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
                            'totalFinalClass' => 0,
                        ];
                    })
                    ->filter()
                    ->values(),
            ];
        })->values();

        return response()->json($grouped);
    }
}
