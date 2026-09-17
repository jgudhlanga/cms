<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\Reconciliation\StudyPositionBulkConfirmRequest;
use App\Http\Requests\Institution\Reconciliation\StudyPositionListRequest;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Institution\Reconciliation\DepartmentStudyPositionListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentStudyPositionReconciliationController extends Controller
{
    public function show(Request $request, InstitutionDepartment $department): Response
    {
        $this->authorizeAccess($request, $department);

        return Inertia::render(
            'institution/departments/reconciliation/StudyPositionReconciliation',
            DepartmentDataReconciliationController::pageProps($department, $request) + [
                'initialState' => StudyPositionStateEnum::tryFrom((string) $request->query('state'))?->value
                    ?? StudyPositionStateEnum::UNCONFIRMED->value,
            ],
        );
    }

    public function list(
        StudyPositionListRequest $request,
        InstitutionDepartment $department,
        DepartmentStudyPositionListService $listService,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json(
            $listService->list(
                $department,
                StudyPositionStateEnum::from($validated['state']),
                isset($validated['mode_of_study_id']) ? (int) $validated['mode_of_study_id'] : null,
                isset($validated['department_level_id']) ? (int) $validated['department_level_id'] : null,
                isset($validated['department_course_id']) ? (int) $validated['department_course_id'] : null,
            ),
        );
    }

    /**
     * Bulk-confirms that the phase currently on record is correct — the same write a registrar
     * would make one at a time from each student's profile, applied to every selected enrolment.
     * An enrolment with no phase on record, or one the guards refuse, is reported and skipped
     * rather than failing the whole batch.
     */
    public function confirm(
        StudyPositionBulkConfirmRequest $request,
        InstitutionDepartment $department,
        ProgrammeSemesterResolver $programmeSemesterResolver,
        ConfirmStudyPositionAction $confirmStudyPosition,
    ): JsonResponse {
        $user = $request->user();
        $reason = (string) $request->validated('reason');
        $ids = $request->validated('enrolment_ids');

        $enrolments = StudentEnrolment::query()
            ->whereIn('id', $ids)
            ->where('institution_department_id', $department->id)
            ->with(['studentSemesters.semester', 'studentSemesters.programmeSemester'])
            ->get()
            ->keyBy('id');

        $confirmed = 0;
        $rows = [];

        foreach ($ids as $id) {
            $enrolment = $enrolments->get($id);

            if (! $enrolment instanceof StudentEnrolment) {
                $rows[] = ['enrolmentId' => $id, 'status' => 'skipped', 'reason' => __('students.study_position_invalid_programme')];

                continue;
            }

            $student = Student::query()->withoutGlobalScopes()->find($enrolment->student_id);

            // Department reach and the confirm-study-position ability are already checked for the
            // whole request; this only rules out a staff member who is also the student.
            if (! $student instanceof Student || (int) $user?->studentProfile?->id === (int) $student->id) {
                $rows[] = ['enrolmentId' => $id, 'status' => 'skipped', 'reason' => __('students.study_position_modal_no_access')];

                continue;
            }

            $systemRow = $enrolment->currentStudentSemester();
            $phase = $systemRow?->programmeSemester
                ?? ($systemRow !== null ? $programmeSemesterResolver->programmeSemesterForStudentSemester($systemRow) : null);

            if ($phase === null) {
                $rows[] = ['enrolmentId' => $id, 'status' => 'skipped', 'reason' => __('students.study_position_records_missing')];

                continue;
            }

            try {
                $confirmStudyPosition->execute(
                    $enrolment,
                    StudyPositionAnswerEnum::PHASE,
                    $phase,
                    StudyPositionSourceEnum::ADMIN,
                    $user,
                    $reason,
                );
                $confirmed++;
                $rows[] = ['enrolmentId' => $id, 'status' => 'confirmed'];
            } catch (ValidationException $exception) {
                $rows[] = [
                    'enrolmentId' => $id,
                    'status' => 'skipped',
                    'reason' => (string) collect($exception->errors())->flatten()->first(),
                ];
            }
        }

        return response()->json([
            'summary' => [
                'requested' => count($ids),
                'confirmed' => $confirmed,
                'skipped' => count($rows) - $confirmed,
            ],
            'rows' => $rows,
        ]);
    }

    private function authorizeAccess(Request $request, InstitutionDepartment $department): void
    {
        $user = $request->user();

        abort_unless($user?->can('confirm-study-position:students') === true, 403);
        $this->authorize('viewDepartmentMetaData', $department);
    }
}
