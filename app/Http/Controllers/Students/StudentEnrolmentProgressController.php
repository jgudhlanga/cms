<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\UpdateStudentEnrolmentStatusAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentEnrolmentStatusRequest;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use Illuminate\Http\RedirectResponse;
use Throwable;

class StudentEnrolmentProgressController extends Controller
{
    public function __construct(
        protected UpdateStudentEnrolmentStatusAction $updateStudentEnrolmentStatus,
    ) {}

    public function updateStatus(
        UpdateStudentEnrolmentStatusRequest $request,
        Student $student,
        StudentEnrolment $studentEnrolment,
    ): RedirectResponse {
        $this->authorize('update', $student);

        abort_unless((int) $studentEnrolment->student_id === (int) $student->id, 404);

        return $this->applyStatusUpdate($studentEnrolment, (string) $request->validated('status'));
    }

    public function updateSemesterStatus(
        UpdateStudentEnrolmentStatusRequest $request,
        Student $student,
        StudentSemester $studentSemester,
    ): RedirectResponse {
        $this->authorize('update', $student);

        $studentSemester->loadMissing('enrolment');
        $enrolment = $studentSemester->enrolment;

        abort_unless($enrolment instanceof StudentEnrolment && (int) $enrolment->student_id === (int) $student->id, 404);

        return $this->applyStatusUpdate($studentSemester, (string) $request->validated('status'));
    }

    private function applyStatusUpdate(
        StudentEnrolment|StudentSemester $target,
        string $statusSlug,
    ): RedirectResponse {
        try {
            $this->updateStudentEnrolmentStatus->execute($target, $statusSlug);
        } catch (StudentEnrolmentProgressionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'status' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('students.enrolment_status_update_failed'),
            ]);
        }

        return back()->with('success', __('students.enrolment_status_updated'));
    }
}
