<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentIdCardRequestRequest;
use App\Http\Requests\Students\UploadStudentIdCardPhotoRequest;
use App\Http\Resources\Students\StudentIdCardRequestResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use App\Services\Students\StudentIdCardRequestService;
use App\Support\Students\StudentIdCardFace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdCardRequestController extends Controller
{
    public function __construct(
        private readonly StudentIdCardRequestService $idCardRequestService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('create', StudentIdCardRequest::class);

        $student = $this->student($request);
        $student->loadMissing(StudentIdCardFace::RELATIONS);

        $history = $student->idCardRequests()
            ->with([
                'photo',
                'reviewer',
                'printer',
                'issuer',
                ...StudentIdCardFace::requestRelations(),
            ])
            ->latest()
            ->get();

        $latestPhoto = $student->latestIdPhoto();
        $hasStudentNumber = trim((string) $student->student_number) !== '';
        $hasActiveRequest = $student->idCardRequests()
            ->whereIn('status', array_map(
                fn ($status) => $status->value,
                IdCardRequestStatusEnum::activeStatuses(),
            ))
            ->exists();

        return Inertia::render('portal/student/id-card/Index', [
            'student' => StudentResource::make($student),
            'cardFace' => StudentIdCardFace::fromStudent($student)->toArray(),
            'requests' => StudentIdCardRequestResource::collection($history),
            'latestRequest' => $history->first()
                ? StudentIdCardRequestResource::make($history->first())
                : null,
            'latestPhotoUrl' => $latestPhoto?->getFullUrl('card') ?? $latestPhoto?->getFullUrl(),
            'hasPhoto' => $latestPhoto !== null,
            'hasStudentNumber' => $hasStudentNumber,
            'canSubmit' => $hasStudentNumber && ! $hasActiveRequest,
            'reasons' => IdCardRequestReasonEnum::options(),
            'feeAmount' => $this->idCardRequestService->feeAmount(),
            'feeTypeId' => FeeType::query()
                ->where('slug', FeeTypeEnum::STUDENT_ID_FEE->slug())
                ->value('id'),
        ]);
    }

    public function uploadPhoto(UploadStudentIdCardPhotoRequest $request): RedirectResponse
    {
        $student = $this->student($request);
        $this->idCardRequestService->uploadPhoto($student, $request->file('photo'));

        return back()->with('success', __('students.id_card_photo_uploaded'));
    }

    public function store(StoreStudentIdCardRequestRequest $request): RedirectResponse
    {
        $student = $this->student($request);
        $reason = IdCardRequestReasonEnum::from($request->validated('reason'));

        $this->idCardRequestService->submit(
            $student,
            $reason,
            $request->validated('notes'),
        );

        $message = $reason->requiresFee()
            ? __('students.id_card_request_awaiting_payment')
            : __('students.id_card_request_submitted');

        return back()->with('success', $message);
    }

    private function student(Request $request): Student
    {
        $student = $request->user()?->studentProfile;
        abort_if($student === null, 404);

        return $student;
    }
}
