<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\JsonResponse;

class DepartmentAcademicCalendarController extends Controller
{
    public function __construct()
    {
    }

    public function departmentAcademicCalendar(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $institutionDepartment = InstitutionDepartment::with([
            'departmentCourses.course',
            'departmentCourses.departmentCourseLevels'
        ])->findOrFail($institutionDepartment->id);
        $grouped = $institutionDepartment->departmentCourses->map(function ($course) use ($institutionDepartment) {
            return [
                'institutionDepartmentId' => $institutionDepartment->id,
                'departmentCourseId' => (string)$course->id,
                'courseName' => $course->course->name,
                'levels' => $course->departmentCourseLevels->map(function ($levelCourse) {
                    return [
                        'departmentLevelId' => (string)$levelCourse->departmentLevel->id,
                        'levelName' => $levelCourse->departmentLevel->level->name,
                        'studentsPerClass' => 0,
                        'totalFinalClass' => 0,
                    ];
                })->values(),
            ];
        })->values();
        return response()->json($grouped);
    }
}
