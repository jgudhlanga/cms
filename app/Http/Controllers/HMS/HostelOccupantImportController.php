<?php

declare(strict_types=1);

namespace App\Http\Controllers\HMS;

use App\Exports\HMS\HostelOccupantImportTemplateExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\HostelOccupantImportPreviewRequest;
use App\Http\Requests\HMS\HostelOccupantImportProcessRequest;
use App\Models\HMS\Hostel;
use App\Services\HMS\HostelOccupantImportService;
use App\Services\HMS\HostelOccupantImportTemplateService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HostelOccupantImportController extends Controller
{
    public function show(Hostel $hostel): Response
    {
        $this->authorizeImport($hostel);

        return Inertia::render('hms/hostels/occupants/Import', [
            'hostel' => [
                'id' => $hostel->id,
                'name' => $hostel->name,
            ],
            'canConfirmPayments' => (bool) request()->user()?->can('confirm:hostel-payments'),
        ]);
    }

    public function template(
        Hostel $hostel,
        HostelOccupantImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorizeImport($hostel);

        $data = $templateService->assemble($hostel);

        return Excel::download(
            new HostelOccupantImportTemplateExport($data),
            $templateService->downloadFileName($hostel),
        );
    }

    public function preview(
        HostelOccupantImportPreviewRequest $request,
        Hostel $hostel,
        HostelOccupantImportService $importService,
    ): JsonResponse {
        $this->authorizeImport($hostel);

        $file = $request->file('file');

        if ($file === null) {
            abort(422);
        }

        return response()->json($importService->preview($file, $hostel));
    }

    public function process(
        HostelOccupantImportProcessRequest $request,
        Hostel $hostel,
        HostelOccupantImportService $importService,
    ): JsonResponse {
        $this->authorizeImport($hostel);
        $this->authorizeProcess();

        /** @var list<array{rowNumber: int, studentId: int, disability?: string|null, hostelRoomId: int, hostelRoomSectionId: int}> $rows */
        $rows = $request->validated('rows');

        return response()->json($importService->process($rows, $hostel));
    }

    private function authorizeImport(Hostel $hostel): void
    {
        $this->authorize('view', $hostel);
        abort_unless((bool) request()->user()?->can('import:hostel-applications'), 403);

        $hostelIds = Helper::resolveUserHostels();

        if ($hostelIds !== null && ! in_array((int) $hostel->id, $hostelIds, true)) {
            abort(403);
        }
    }

    private function authorizeProcess(): void
    {
        abort_unless((bool) request()->user()?->can('confirm:hostel-payments'), 403);
        abort_unless((bool) request()->user()?->can('create:hostel-applications'), 403);
    }
}
