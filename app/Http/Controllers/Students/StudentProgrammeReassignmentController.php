<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\ReassignStudentProgrammeBulkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ReassignStudentProgrammeRequest;
use App\Models\Students\StudentApplication;
use App\Queries\Students\ProgrammeOfferingUsageQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentProgrammeReassignmentController extends Controller
{
    public function usage(Request $request, ProgrammeOfferingUsageQuery $query): JsonResponse
    {
        $this->authorize('viewAny', StudentApplication::class);

        $validated = $request->validate([
            'department_course_id' => ['nullable', 'integer', 'exists:department_courses,id'],
            'department_level_id' => ['nullable', 'integer', 'exists:department_levels,id'],
            'mode_of_study_ids' => ['nullable', 'array'],
            'mode_of_study_ids.*' => ['integer'],
            'application_ids' => ['nullable', 'array'],
            'application_ids.*' => ['integer', 'exists:student_applications,id'],
            'student_enrolment_ids' => ['nullable', 'array'],
            'student_enrolment_ids.*' => ['integer', 'exists:student_enrolments,id'],
        ]);

        $applicationIds = array_values(array_filter(array_map(
            'intval',
            $validated['application_ids'] ?? [],
        )));
        $enrolmentIds = array_values(array_filter(array_map(
            'intval',
            $validated['student_enrolment_ids'] ?? [],
        )));

        if ($applicationIds !== [] || $enrolmentIds !== []) {
            return response()->json([
                'data' => $query->recordsForIds($applicationIds, $enrolmentIds),
            ]);
        }

        $departmentCourseId = (int) ($validated['department_course_id'] ?? 0);
        $departmentLevelId = (int) ($validated['department_level_id'] ?? 0);

        if ($departmentCourseId < 1 || $departmentLevelId < 1) {
            return response()->json([
                'message' => __('students.reassign_programme_ids_required'),
            ], 422);
        }

        $modeIds = array_values(array_filter(array_map(
            'intval',
            $validated['mode_of_study_ids'] ?? [],
        )));

        return response()->json([
            'data' => $query->records($departmentCourseId, $departmentLevelId, $modeIds),
        ]);
    }

    public function store(
        ReassignStudentProgrammeRequest $request,
        ReassignStudentProgrammeBulkAction $bulk,
    ): RedirectResponse {
        $result = $bulk->execute(
            $request->user(),
            $request->applicationIds(),
            $request->studentEnrolmentIds(),
            $request->target(),
        );

        if ($result['moved'] === 0 && $result['skipped'] !== []) {
            return back()->withErrors([
                'application_ids' => $result['skipped'][0]['reason'] ?? __('students.reassign_programme_failed'),
            ]);
        }

        $message = trans_choice('students.reassign_programme_moved', $result['moved'], [
            'count' => $result['moved'],
        ]);

        if ($result['class_unassigned'] > 0) {
            $message .= ' '.__('students.reassign_programme_class_unassigned', [
                'count' => $result['class_unassigned'],
            ]);
        }

        if ($result['skipped'] !== []) {
            $message .= ' '.__('students.reassign_programme_skipped', [
                'count' => count($result['skipped']),
            ]);
        }

        return back()->with('success', $message);
    }
}
