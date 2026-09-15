<?php

namespace App\Http\Controllers\Students;

use App\Enums\Shared\AcademicLevelEnum;
use App\Helpers\StudentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\OLevelResultRequest;
use App\Http\Resources\Students\OLevelSubjectResultResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Students\Student;
use App\Models\Students\StudentAcademicResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentOLevelResultsController extends Controller
{
    public function __construct() {}

    public function index(): Response
    {
        $this->authorize('manageStudentAcademicRecords');
        $studentModel = $this->getStudent(request());
        $student = StudentResource::make($studentModel);

        return Inertia::render('portal/student/OLevels', compact('student'));
    }

    public function manage(): Response
    {
        $this->authorize('manageStudentAcademicRecords');
        $studentModel = $this->getStudent(request());
        $student = StudentResource::make($studentModel);
        $oLevelSubjectResults = StudentHelper::getOLevelSubjectsLeftJoinedToStudentResults($studentModel);
        $oLevelSubjectResults = OLevelSubjectResultResource::collection($oLevelSubjectResults);

        return Inertia::render('portal/student/ManageOLevelResults', compact('oLevelSubjectResults', 'student'));
    }

    public function loadStudentOLevelResults(Student $student)
    {
        $this->authorize('view', $student);
        $oLevelResults = StudentHelper::getStudentOLevelResultsJoinedToSubjects($student);

        return OLevelSubjectResultResource::collection($oLevelResults);
    }

    public function store(Student $student, OLevelResultRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentAcademicRecords');
        $this->authorizeStudentRecord($request, (int) $student->id);
        $student->oLevelResults()->updateOrCreate(
            [
                'academic_level_id' => AcademicLevelEnum::SECONDARY_SCHOOL->id(),
                'subject_id' => $request->subject_id,
            ],
            [
                'exam_year' => $request->exam_year,
                'exam_sitting' => $request->exam_sitting,
                'grade_id' => $request->grade_id,
            ]
        );

        return back()->with('success', 'O-Level result saved successfully.');
    }

    public function update(StudentAcademicResult $studentAcademicResult, OLevelResultRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentAcademicRecords');
        $this->authorizeStudentRecord($request, (int) $studentAcademicResult->student_id);
        $studentAcademicResult->update([
            'exam_year' => $request->exam_year,
            'exam_sitting' => $request->exam_sitting,
            'grade_id' => $request->grade_id,
        ]);

        return back()->with('success', 'O-Level result updated successfully.');
    }

    public function destroy(Request $request, StudentAcademicResult $studentAcademicResult): RedirectResponse
    {
        $this->authorize('manageStudentAcademicRecords');
        $this->authorizeStudentRecord($request, (int) $studentAcademicResult->student_id);
        $studentAcademicResult->forceDelete();

        return back()->with('success', 'O-Level result deleted successfully.');
    }

    /**
     * O-level results are managed by the student they belong to, or by staff allowed to update that student.
     */
    private function authorizeStudentRecord(Request $request, int $studentId): void
    {
        $ownStudentId = $this->getStudent($request)?->id;

        if ($ownStudentId !== null && (int) $ownStudentId === $studentId) {
            return;
        }

        $student = Student::query()->find($studentId);

        abort_unless($student !== null && $request->user()->can('update', $student), 403);
    }

    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }
}
