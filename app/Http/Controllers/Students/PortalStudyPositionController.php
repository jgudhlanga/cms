<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ConfirmOwnStudyPositionRequest;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Services\Students\StudyPosition\StudyPositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A student confirming which programme phase they are studying this period.
 */
class PortalStudyPositionController extends Controller
{
    public function __construct(
        private readonly StudyPositionService $studyPosition,
        private readonly ConfirmStudyPositionAction $confirmStudyPosition,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorize('confirmOwnStudyPosition');

        return response()->json($this->studyPosition->statusFor($this->ownStudent($request), $request->user()));
    }

    public function store(ConfirmOwnStudyPositionRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // The audit trail would name the student for an answer staff gave; staff use the admin profile.
        abort_if($user->isImpersonated(), 403, __('students.study_position_impersonation_blocked'));

        $student = $this->ownStudent($request);
        $status = $this->studyPosition->statusFor($student, $user, autoDetect: false);
        $answerable = collect($status['items'])
            ->filter(fn (array $item): bool => $item['studentCanAnswer'])
            ->keyBy('enrolmentId');

        $answers = $request->validated('answers');

        foreach ($answers as $index => $answer) {
            if (! $answerable->has((int) $answer['student_enrolment_id'])) {
                throw ValidationException::withMessages([
                    "answers.{$index}.student_enrolment_id" => __('students.study_position_invalid_programme'),
                ]);
            }
        }

        $enrolments = StudentEnrolment::query()
            ->whereIn('id', array_map(static fn (array $answer): int => (int) $answer['student_enrolment_id'], $answers))
            ->get()
            ->keyBy('id');

        // All programmes or none, so the student never has to work out which ones were saved.
        $confirmations = DB::transaction(function () use ($answers, $enrolments, $user): array {
            $saved = [];

            foreach ($answers as $index => $answer) {
                $saved[] = $this->confirmOne($index, $answer, $enrolments->get((int) $answer['student_enrolment_id']), $user);
            }

            return $saved;
        });

        StudyPositionService::forget((int) $student->id);

        return back()->with('success', $this->successMessage($confirmations, (string) $status['periodLabel']));
    }

    /**
     * @param  array{student_enrolment_id: int|string, answer: string, programme_semester_id?: int|string|null}  $answer
     */
    private function confirmOne(int $index, array $answer, ?StudentEnrolment $enrolment, User $user): ?StudentStudyPositionConfirmation
    {
        $field = "answers.{$index}.programme_semester_id";
        $answerType = StudyPositionAnswerEnum::from((string) $answer['answer']);
        $phase = null;

        if ($answerType === StudyPositionAnswerEnum::PHASE) {
            $phase = ProgrammeSemester::query()->find((int) ($answer['programme_semester_id'] ?? 0));

            if (! $phase instanceof ProgrammeSemester) {
                throw ValidationException::withMessages([$field => __('students.study_position_invalid_phase')]);
            }
        }

        if (! $enrolment instanceof StudentEnrolment) {
            throw ValidationException::withMessages([$field => __('students.study_position_invalid_programme')]);
        }

        try {
            return $this->confirmStudyPosition->execute(
                $enrolment,
                $answerType,
                $phase,
                StudyPositionSourceEnum::STUDENT,
                $user,
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                $field => (string) Arr::first(Arr::flatten($exception->errors())),
            ]);
        }
    }

    /**
     * @param  list<StudentStudyPositionConfirmation|null>  $confirmations
     */
    private function successMessage(array $confirmations, string $periodLabel): string
    {
        $confirmations = array_filter($confirmations);

        foreach ($confirmations as $confirmation) {
            if ($confirmation->sync_status === StudyPositionSyncStatusEnum::NEEDS_REVIEW) {
                return __('students.study_position_saved_review');
            }
        }

        foreach ($confirmations as $confirmation) {
            if ($confirmation->answer->isFollowUp()) {
                return __('students.study_position_saved_follow_up', ['period' => $periodLabel]);
            }
        }

        return __('students.study_position_saved', ['period' => $periodLabel]);
    }

    private function ownStudent(Request $request): Student
    {
        $student = $request->user()?->studentProfile;

        abort_unless($student instanceof Student, 403);

        return $student;
    }
}
