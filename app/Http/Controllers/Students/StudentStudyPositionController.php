<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ConfirmStudentStudyPositionRequest;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudyPosition\StudyPositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Registry, registrar and HOD confirmation of a student's study position from the admin profile.
 */
class StudentStudyPositionController extends Controller
{
    public function __construct(
        private readonly StudyPositionService $studyPosition,
        private readonly ConfirmStudyPositionAction $confirmStudyPosition,
    ) {}

    public function show(Request $request, Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        $user = $request->user();

        // Students may view their own record, but review notes and reasons are for staff only.
        abort_unless($user->can('viewAny:students') || $user->can('view:students'), 403);

        return response()->json($this->studyPosition->statusFor($student, $user, forAdmin: true));
    }

    public function update(ConfirmStudentStudyPositionRequest $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $reason = (string) $request->validated('reason');
        $inScope = $this->studyPosition->inScopeEnrolments($student)->keyBy('id');
        $positions = [];
        $errors = [];

        // Authorise every programme before writing any of them.
        foreach ($request->validated('positions') as $index => $position) {
            $field = "positions.{$index}.programme_semester_id";
            $enrolment = $inScope->get((int) $position['student_enrolment_id']);

            if (! $enrolment instanceof StudentEnrolment) {
                $errors[$field] = __('students.study_position_invalid_programme');

                continue;
            }

            $this->authorize('confirmStudyPosition', [$student, $enrolment]);

            $phase = ProgrammeSemester::query()->find((int) $position['programme_semester_id']);

            if (! $phase instanceof ProgrammeSemester) {
                $errors[$field] = __('students.study_position_invalid_phase');

                continue;
            }

            $positions[$field] = [$enrolment, $phase];
        }

        $applied = 0;

        foreach ($positions as $field => [$enrolment, $phase]) {
            try {
                $this->confirmStudyPosition->execute(
                    $enrolment,
                    StudyPositionAnswerEnum::PHASE,
                    $phase,
                    StudyPositionSourceEnum::ADMIN,
                    $user,
                    $reason,
                );
                $applied++;
            } catch (ValidationException $exception) {
                $errors[$field] = (string) Arr::first(Arr::flatten($exception->errors()));
            }
        }

        $periodLabel = $this->studyPosition->statusFor($student, $user, forAdmin: true, autoDetect: false)['periodLabel'];

        if ($errors !== []) {
            return back()
                ->withErrors($errors)
                ->with('warning', __('students.study_position_admin_partial', [
                    'applied' => $applied,
                    'failed' => count($errors),
                ]));
        }

        return back()->with('success', __('students.study_position_admin_saved', ['period' => (string) $periodLabel]));
    }
}
