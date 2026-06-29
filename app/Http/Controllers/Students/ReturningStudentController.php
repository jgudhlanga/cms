<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\ContinueStudentEnrolmentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Students\Student;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ReturningStudentContextService;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReturningStudentController extends Controller
{
    public function __construct(
        protected ReturningStudentContextService $returningStudentContext,
        protected ApplicationFeeService $applicationFeeService,
        protected ContinueStudentEnrolmentAction $continueStudentEnrolmentAction,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function showContinue(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->student();

        if (! $this->returningStudentContext->needsContinueInClassPage($student)) {
            return to_route('portal.profile.applications');
        }

        return Inertia::render('portal/returning-student/ContinueInClass', [
            'openIntakes' => IntakePeriodResource::collection($this->returningStudentContext->openIntakes()),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function continueInClass(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->student();

        if (! $this->returningStudentContext->canContinueInClass($student)) {
            return back()->withErrors(['student_number' => __('trans.returning_student_continuation_unavailable')]);
        }

        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'intake_period_id' => ['required', 'integer', 'exists:intake_periods,id'],
            'acknowledged' => ['accepted'],
        ]);

        $enteredNumber = EnrollmentLookupService::normalizeStudentNumber($data['student_number']);
        $profileNumber = EnrollmentLookupService::normalizeStudentNumber((string) $student->student_number);

        if ($enteredNumber !== $profileNumber) {
            return back()->withErrors(['student_number' => __('trans.returning_student_number_mismatch')]);
        }

        $intakePeriod = $this->applicationFeeService->assertPortalIntakePeriod((int) $data['intake_period_id']);
        $application = $this->returningStudentContext->qualifyingApplicationForContinuation($student);

        if ($application === null) {
            return back()->withErrors(['student_number' => __('trans.returning_student_continuation_unavailable')]);
        }

        DB::transaction(function () use ($student, $application, $intakePeriod): void {
            $this->continueStudentEnrolmentAction->execute($application);
            $this->returningStudentContext->persistAcknowledgement(
                $student,
                'continuation',
                $intakePeriod,
                (int) $application->id,
            );
        });

        return to_route('portal.profile.applications')->with('success', __('trans.returning_student_continuation_success'));
    }

    private function student(): Student
    {
        $student = request()->user()?->studentProfile;
        abort_if($student === null, 403);

        return $student;
    }
}
