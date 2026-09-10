<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\InstitutionDepartment;
use App\Services\Institution\Reconciliation\DepartmentEnrolmentCountsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentDataReconciliationController extends Controller
{
    public function counts(
        Request $request,
        InstitutionDepartment $department,
        DepartmentEnrolmentCountsService $countsService,
    ): JsonResponse {
        $this->authorize('viewDepartmentMetaData', $department);

        $calendarYear = (int) ($request->query('calendar_year') ?: now()->format('Y'));
        $modeOfStudyId = $request->query('mode_of_study_id');
        $departmentLevelId = $request->query('department_level_id');
        $departmentCourseId = $request->query('department_course_id');

        return response()->json(
            $countsService->resolve(
                $department,
                $calendarYear,
                is_numeric($modeOfStudyId) ? (int) $modeOfStudyId : null,
                is_numeric($departmentLevelId) ? (int) $departmentLevelId : null,
                is_numeric($departmentCourseId) ? (int) $departmentCourseId : null,
            ),
        );
    }

    /**
     * @return array{department: InstitutionDepartmentResource, calendarYear: int, modeOfStudyId: int|null}
     */
    public static function pageProps(InstitutionDepartment $department, Request $request): array
    {
        $department->loadMissing('department');

        $calendarYear = (int) ($request->query('academic_year') ?: $request->query('calendar_year') ?: now()->format('Y'));
        $modeOfStudyId = $request->query('mode_of_study_id');

        return [
            'department' => new InstitutionDepartmentResource($department),
            'calendarYear' => $calendarYear,
            'modeOfStudyId' => is_numeric($modeOfStudyId) ? (int) $modeOfStudyId : null,
        ];
    }
}
