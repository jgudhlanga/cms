<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\LookupStudentExamResultRequest;
use App\Http\Resources\Students\StudentExamResultResource;
use App\Models\Students\Student;
use App\Models\Students\StudentExamResult;
use App\Services\Students\StudentExamResultAccessService;
use App\Services\Students\StudentExamResultLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class StudentExamResultController extends Controller
{
    public function __construct(
        private readonly StudentExamResultAccessService $accessService,
        private readonly StudentExamResultLookupService $lookupService,
    ) {}

    public function index(Student $student): JsonResponse
    {
        $this->authorizeView();

        $access = $this->accessService->evaluate($student);
        $savedResults = $this->lookupService->listForStudent($student);

        return response()->json([
            'data' => [
                'access' => $access,
                'savedResults' => StudentExamResultResource::collection($savedResults)->resolve(),
                'hasUnclaimedSession' => $this->lookupService->hasUnclaimedSession($student),
                'logBookFeeGapNotice' => __('trans.log_book_fee_gap'),
            ],
        ]);
    }

    public function show(Student $student, StudentExamResult $studentExamResult): JsonResponse
    {
        $this->authorizeView();
        abort_unless((int) $studentExamResult->student_id === (int) $student->id, 404);

        $payload = $this->lookupService->showForStudent($student, $studentExamResult);

        return response()->json([
            'data' => [
                'access' => $payload['access'],
                'allowed' => $payload['allowed'],
                'summary' => $payload['summary'],
                'subjects' => $payload['subjects'],
                'logBookFeeGapNotice' => __('trans.log_book_fee_gap'),
            ],
        ]);
    }

    public function lookup(LookupStudentExamResultRequest $request, Student $student): JsonResponse
    {
        $this->authorizeView();

        $rateLimitKey = 'admin-exam-results-lookup:'.$request->user()->id.':'.$student->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            Log::info('exam_results.admin_rate_limited', [
                'user_id' => $request->user()->id,
                'student_id' => $student->id,
            ]);

            throw ValidationException::withMessages([
                'candidate_number' => [__('examinations.exam_results_rate_limited')],
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $result = $this->lookupService->lookup(
                $student,
                $request->string('candidate_number')->toString(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        if (! $result['found']) {
            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'found' => false,
                    'summary' => null,
                    'subjects' => [],
                    'savedResults' => StudentExamResultResource::collection(
                        $this->lookupService->listForStudent($student)
                    )->resolve(),
                    'hasUnclaimedSession' => $this->lookupService->hasUnclaimedSession($student),
                    'access' => $this->accessService->evaluate($student),
                ],
            ], 422);
        }

        $studentExamResult = $result['studentExamResult'];
        $showPayload = $studentExamResult instanceof StudentExamResult
            ? $this->lookupService->showForStudent($student, $studentExamResult)
            : [
                'access' => $this->accessService->evaluate($student),
                'allowed' => false,
                'summary' => $result['summary'],
                'subjects' => $result['subjects'],
            ];

        return response()->json([
            'message' => __('examinations.exam_results_lookup'),
            'data' => [
                'found' => true,
                'access' => $showPayload['access'],
                'allowed' => $showPayload['allowed'],
                'summary' => $showPayload['summary'],
                'subjects' => $showPayload['subjects'],
                'savedResults' => StudentExamResultResource::collection(
                    $this->lookupService->listForStudent($student)
                )->resolve(),
                'hasUnclaimedSession' => $this->lookupService->hasUnclaimedSession($student),
            ],
        ]);
    }

    private function authorizeView(): void
    {
        abort_unless(
            request()->user()?->can('viewStudentExamResults:students') ?? false,
            403,
            __('examinations.exam_results_permission_denied'),
        );
    }
}
