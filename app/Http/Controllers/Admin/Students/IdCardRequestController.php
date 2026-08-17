<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Students;

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\RejectStudentIdCardRequestRequest;
use App\Http\Resources\Students\StudentIdCardRequestResource;
use App\Http\Resources\Students\StudentIdCardSettingResource;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Students\StudentIdCardSetting;
use App\Services\Students\StudentIdCardRequestService;
use App\Support\Students\StudentIdCardFace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class IdCardRequestController extends Controller
{
    public function __construct(
        private readonly StudentIdCardRequestService $idCardRequestService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize('viewAny', StudentIdCardRequest::class);

        return Inertia::render('students/id-card-requests/Index', [
            'idCardSettings' => StudentIdCardSettingResource::make(
                StudentIdCardSetting::resolveForTenant(),
            ),
            'statusOptions' => IdCardRequestStatusEnum::options(),
            'reasonOptions' => IdCardRequestReasonEnum::options(),
            'canBulkPrint' => $this->idCardRequestService->hasApprovedReadyToPrint(),
        ]);
    }

    public function show(StudentIdCardRequest $idCardRequest): InertiaResponse
    {
        $this->authorize('view', $idCardRequest);

        $idCardRequest->loadMissing([
            ...StudentIdCardFace::requestRelations(),
            'photo',
            'reviewer',
            'printer',
            'issuer',
        ]);

        return Inertia::render('students/id-card-requests/Show', [
            'idCardRequest' => StudentIdCardRequestResource::make($idCardRequest),
        ]);
    }

    public function approve(Request $request, StudentIdCardRequest $idCardRequest): RedirectResponse
    {
        $this->authorize('review', $idCardRequest);
        $this->idCardRequestService->approve($idCardRequest, $request->user());

        return back()->with('success', __('students.id_card_request_approved'));
    }

    public function reject(RejectStudentIdCardRequestRequest $request, StudentIdCardRequest $idCardRequest): RedirectResponse
    {
        $this->authorize('review', $idCardRequest);
        $this->idCardRequestService->reject(
            $idCardRequest,
            $request->user(),
            $request->validated('rejection_reason'),
        );

        return back()->with('success', __('students.id_card_request_rejected'));
    }

    public function print(Request $request, StudentIdCardRequest $idCardRequest): SymfonyResponse
    {
        $this->authorize('print', $idCardRequest);

        $result = $this->idCardRequestService->print($idCardRequest, $request->user());

        return new Response($result->pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$result->fileName.'"',
        ]);
    }

    public function bulkPrint(): RedirectResponse|SymfonyResponse
    {
        $this->authorize('export', StudentIdCardRequest::class);

        $result = $this->idCardRequestService->exportApproved();
        if ($result === null) {
            return redirect()
                ->route('admin.students.id-card-requests.index')
                ->with('error', __('students.id_card_bulk_print_empty'));
        }

        return new Response($result->pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$result->fileName.'"',
        ]);
    }

    public function issue(Request $request, StudentIdCardRequest $idCardRequest): RedirectResponse
    {
        $this->authorize('issue', $idCardRequest);
        $this->idCardRequestService->issue($idCardRequest, $request->user());

        return back()->with('success', __('students.id_card_request_issued'));
    }
}
