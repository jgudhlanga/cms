<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Exports\Institution\EnrolmentVsClassListImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\Reconciliation\EnrolmentVsClassListPreviewRequest;
use App\Http\Requests\Institution\Reconciliation\EnrolmentVsClassListProcessRequest;
use App\Models\Institution\InstitutionDepartment;
use App\Services\Institution\Reconciliation\EnrolmentVsClassListImportService;
use App\Services\Institution\Reconciliation\EnrolmentVsClassListImportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DepartmentEnrolmentVsClassListController extends Controller
{
    public function show(Request $request, InstitutionDepartment $department): Response
    {
        $this->authorize('viewDepartmentMetaData');

        return Inertia::render(
            'institution/departments/reconciliation/EnrolmentVsClassList',
            DepartmentDataReconciliationController::pageProps($department, $request),
        );
    }

    public function downloadTemplate(
        InstitutionDepartment $department,
        EnrolmentVsClassListImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('viewDepartmentMetaData');

        $data = $templateService->assemble($department);

        return Excel::download(
            new EnrolmentVsClassListImportTemplateExport($data),
            $templateService->downloadFileName($department),
        );
    }

    public function preview(
        EnrolmentVsClassListPreviewRequest $request,
        InstitutionDepartment $department,
        EnrolmentVsClassListImportService $importService,
    ): JsonResponse {
        $this->authorize('updateDepartmentMetaData');

        $validated = $request->validated();
        $file = $request->file('file');

        if ($file === null) {
            abort(422);
        }

        return response()->json(
            $importService->preview(
                $department,
                $file,
                (int) $validated['calendar_year'],
                isset($validated['mode_of_study_id']) ? (int) $validated['mode_of_study_id'] : null,
                isset($validated['department_level_id']) ? (int) $validated['department_level_id'] : null,
                isset($validated['department_course_id']) ? (int) $validated['department_course_id'] : null,
            ),
        );
    }

    public function process(
        EnrolmentVsClassListProcessRequest $request,
        InstitutionDepartment $department,
        EnrolmentVsClassListImportService $importService,
    ): JsonResponse {
        $this->authorize('updateDepartmentMetaData');

        $validated = $request->validated();

        /** @var list<array{rowNumber: int, studentApplicationId: int}> $rows */
        $rows = $validated['rows'];

        return response()->json(
            $importService->process(
                $department,
                $rows,
                (int) $validated['calendar_year'],
            ),
        );
    }
}
