<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StudentIdCardImportPreviewRequest;
use App\Http\Requests\Students\StudentIdCardImportProcessRequest;
use App\Models\Students\StudentIdCardRequest;
use App\Services\Students\StudentIdCardImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class IdCardImportController extends Controller
{
    public function show(): InertiaResponse
    {
        $this->authorize('import', StudentIdCardRequest::class);

        return Inertia::render('students/id-card-requests/Import');
    }

    public function template(StudentIdCardImportService $importService): Response
    {
        $this->authorize('import', StudentIdCardRequest::class);

        return response($importService->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$importService->templateFileName().'"',
        ]);
    }

    public function preview(
        StudentIdCardImportPreviewRequest $request,
        StudentIdCardImportService $importService,
    ): JsonResponse {
        $file = $request->file('file');
        if ($file === null) {
            abort(422);
        }

        return response()->json($importService->preview($file));
    }

    public function process(
        StudentIdCardImportProcessRequest $request,
        StudentIdCardImportService $importService,
    ): JsonResponse {
        /** @var list<array{rowNumber: int, studentId: int}> $rows */
        $rows = $request->validated('rows');

        return response()->json($importService->process($rows, $request->user()));
    }
}
