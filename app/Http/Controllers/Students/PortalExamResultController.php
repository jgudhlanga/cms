<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\LookupPortalExamResultRequest;
use App\Http\Resources\Students\StudentExamResultResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Students\Student;
use App\Models\Students\StudentExamResult;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\StudentExamResultAccessService;
use App\Services\Students\StudentExamResultLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class PortalExamResultController extends Controller
{
    public function __construct(
        private readonly StudentExamResultAccessService $accessService,
        private readonly StudentExamResultLookupService $lookupService,
        private readonly ApplicationEligibilityService $eligibilityService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewOwnExamResults');

        $student = $request->user()->studentProfile;
        abort_if($student === null, 404);

        $access = $this->accessService->evaluate($student);
        $savedResults = $this->lookupService->listForStudent($student);

        return Inertia::render('portal/student/ExamResults', [
            'student' => StudentResource::make($student->loadMissing(['idType', 'user'])),
            'access' => $access,
            'savedResults' => StudentExamResultResource::collection($savedResults),
            'hasUnclaimedSession' => $this->lookupService->hasUnclaimedSession($student),
            'lookupError' => null,
            'logBookFeeGapNotice' => $this->logBookFeeGapNotice($student),
        ]);
    }

    public function show(Request $request, StudentExamResult $studentExamResult): Response
    {
        $this->authorize('viewOwnExamResults');

        $student = $request->user()->studentProfile;
        abort_if($student === null, 404);

        $payload = $this->lookupService->showForStudent($student, $studentExamResult);

        return Inertia::render('portal/student/ExamResultShow', [
            'student' => StudentResource::make($student->loadMissing(['idType', 'user'])),
            'access' => $payload['access'],
            'allowed' => $payload['allowed'],
            'summary' => $payload['summary'],
            'subjects' => $payload['subjects'],
            'logBookFeeGapNotice' => $this->logBookFeeGapNotice($student),
        ]);
    }

    public function lookup(LookupPortalExamResultRequest $request): RedirectResponse
    {
        $student = $request->user()->studentProfile;
        abort_if($student === null, 404);

        $rateLimitKey = 'exam-results-lookup:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            Log::info('exam_results.rate_limited', [
                'user_id' => $request->user()->id,
                'student_id' => $student->id,
            ]);

            return back()->withErrors([
                'candidate_number' => __('examinations.exam_results_rate_limited'),
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $lookup = $this->lookupService->lookup(
            $student,
            $request->string('candidate_number')->toString()
        );

        if ($lookup['found'] && $lookup['studentExamResult'] instanceof StudentExamResult) {
            Log::info('exam_results.unlocked_view', [
                'student_id' => $student->id,
                'candidate_number' => $lookup['candidateNumber'],
                'gate' => $this->accessService->evaluate($student)['gate'],
            ]);
            RateLimiter::clear($rateLimitKey);

            return redirect()->route('portal.exam-results.show', $lookup['studentExamResult']);
        }

        Log::info('exam_results.unmatched_candidate', [
            'student_id' => $student->id,
            'candidate_number' => $lookup['candidateNumber'],
        ]);

        return back()->withErrors([
            'candidate_number' => $lookup['message'] ?? __('trans.exam_results_not_found'),
        ]);
    }

    private function logBookFeeGapNotice(Student $student): ?string
    {
        $enrolment = $this->accessService->resolveEnrolmentContext($student);
        $mode = $enrolment?->modeOfStudy;

        if ($mode === null || ! $this->eligibilityService->isOjetMode($mode)) {
            return null;
        }

        return __('trans.log_book_fee_gap');
    }
}
