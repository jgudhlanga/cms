<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\CheckNationalIdRequest;
use App\Http\Requests\Guest\CheckPassportRequest;
use App\Http\Requests\Guest\ReturningStudentLookupRequest;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Http\JsonResponse;

class GuestEnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentLookupService $enrollmentLookupService,
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
}
