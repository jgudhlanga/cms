<?php

namespace App\Http\Controllers\Students;

use App\Helpers\StudentHelper;
use App\Http\Requests\Students\OLevelResultRequest;
use App\Models\Students\StudentAcademicResult;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use App\Enums\Shared\{AcademicLevelEnum};
use App\Http\Controllers\Controller;
use App\Http\Resources\Students\{AcademicLevelResource, OLevelSubjectResultResource, StudentResource};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Inertia\Inertia;
use App\Models\Students\Student;

class StudentOLevelResultsController extends Controller
{
    public function __construct()
    {
    }

    public function index(): Response
    {
        $this->authorize('manageStudentAcademicRecords');
        $studentModel = $this->getStudent(request());
        $student = StudentResource::make($studentModel);

        return Inertia::render('portal/student/OLevels', compact( 'student'));
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
        $oLevelResults = OLevelSubjectResultResource::collection(StudentHelper::getStudentOLevelResultsJoinedToSubjects($student));
        return OLevelSubjectResultResource::collection($oLevelResults);
    }

    public function store(Student $student, OLevelResultRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentAcademicRecords');
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
        $studentAcademicResult->update([
            'exam_year' => $request->exam_year,
            'exam_sitting' => $request->exam_sitting,
            'grade_id' => $request->grade_id,
        ]);

        return back()->with('success', 'O-Level result updated successfully.');
    }

    public function destroy(StudentAcademicResult $studentAcademicResult): RedirectResponse
    {
        $this->authorize('manageStudentAcademicRecords');
        $studentAcademicResult->forceDelete();
        return back()->with('success', 'O-Level result deleted successfully.');
    }


    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }

}
