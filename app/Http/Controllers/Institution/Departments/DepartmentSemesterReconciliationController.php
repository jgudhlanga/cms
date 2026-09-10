<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Exports\Institution\SemesterReconciliationImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\Reconciliation\SemesterReconciliationPreviewRequest;
use App\Http\Requests\Institution\Reconciliation\SemesterReconciliationProcessRequest;
use App\Models\Institution\InstitutionDepartment;
use App\Services\Institution\Reconciliation\SemesterReconciliationImportService;
use App\Services\Institution\Reconciliation\SemesterReconciliationImportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DepartmentSemesterReconciliationController extends Controller
{
    public function show(Request $request, InstitutionDepartment $department): Response
    {
        $this->authorize('viewDepartmentMetaData', $department);

        return Inertia::render(
            'institution/departments/reconciliation/SemesterReconciliation',
            DepartmentDataReconciliationController::pageProps($department, $request),
        );
    }

    public function downloadTemplate(
        InstitutionDepartment $department,
        SemesterReconciliationImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('viewDepartmentMetaData', $department);

        $data = $templateService->assemble($department);

        return Excel::download(
            new SemesterReconciliationImportTemplateExport($data, $department),
            $templateService->downloadFileName($department),
        );
    }

    public function preview(
        SemesterReconciliationPreviewRequest $request,
        InstitutionDepartment $department,
        SemesterReconciliationImportService $importService,
    ): JsonResponse {
        $this->authorize('updateDepartmentMetaData', $department);

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
        SemesterReconciliationProcessRequest $request,
        InstitutionDepartment $department,
        SemesterReconciliationImportService $importService,
    ): JsonResponse {
        $this->authorize('updateDepartmentMetaData', $department);

        $validated = $request->validated();

        /** @var list<array{rowNumber: int, studentEnrolmentId: int, programmeSemesterId: int}> $rows */
        $rows = $validated['rows'];

        return response()->json(
            $importService->process($department, $rows),
        );
    }
}
