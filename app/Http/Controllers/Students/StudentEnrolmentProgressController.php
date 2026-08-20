<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\UpdateStudentEnrolmentStatusAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentEnrolmentStatusRequest;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use Illuminate\Http\RedirectResponse;

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

        try {
            $this->updateStudentEnrolmentStatus->execute(
                $studentEnrolment,
                (string) $request->validated('status'),
            );
        } catch (StudentEnrolmentProgressionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('success', __('students.enrolment_status_updated'));
    }
}
