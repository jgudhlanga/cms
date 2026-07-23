<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Enums\Students\ApplicationTrackEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\CheckNationalIdRequest;
use App\Http\Requests\Guest\CheckPassportRequest;
use App\Http\Requests\Guest\ReturningStudentLookupRequest;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestEnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentLookupService $enrollmentLookupService,
        protected RegistrationProgrammeAvailabilityService $programmeAvailability,
    ) {}

    public function checkNationalId(CheckNationalIdRequest $request): JsonResponse
    {
        $result = $this->enrollmentLookupService->checkNationalIdDuplicate(
            $request->string('id_number')->toString()
        );

        return response()->json($result);
    }

    public function checkPassport(CheckPassportRequest $request): JsonResponse
    {
        $result = $this->enrollmentLookupService->checkPassportDuplicate(
            $request->string('passport_number')->toString()
        );

        return response()->json($result);
    }

    public function lookup(ReturningStudentLookupRequest $request): JsonResponse
    {
        $result = $this->enrollmentLookupService->lookupReturning(
            $request->string('type')->toString(),
            $request->string('value')->toString(),
        );

        return response()->json($result);
    }

    public function programmes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'track' => ['required', 'string', 'in:'.implode(',', array_column(ApplicationTrackEnum::cases(), 'value'))],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'continuous_focus' => ['nullable', 'string', 'in:sdp,ojet'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
        ]);

        $track = ApplicationTrackEnum::from($data['track']);

        $tree = $this->programmeAvailability->programmeTree(
            $track,
            (int) $data['level_id'],
            $data['continuous_focus'] ?? null,
        );

        return response()->json($tree);
    }
}
