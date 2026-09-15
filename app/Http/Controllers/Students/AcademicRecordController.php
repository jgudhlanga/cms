<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\AcademicRecordDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\AcademicRecordRequest;
use App\Models\Students\AcademicRecord;
use App\Models\Students\Student;
use App\Repositories\Students\interface\IAcademicRecordRepository;
use Illuminate\Http\Request;

class AcademicRecordController extends Controller
{
    public function __construct(protected IAcademicRecordRepository $repository) {}

    /**
     * Store a newly created academicRecord.
     */
    public function store(AcademicRecordRequest $request)
    {
        $this->authorize('manageStudentAcademicRecords');
        abort_if($this->getStudent($request) === null, 403);

        $this->repository->create(
            AcademicRecordDto::fromAcademicRecordRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Update the specified academicRecord.
     */
    public function update(AcademicRecordRequest $request, AcademicRecord $academicRecord)
    {
        $this->authorizeAcademicRecord($request, $academicRecord);
        $this->repository->update(
            $academicRecord,
            AcademicRecordDto::fromAcademicRecordRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Soft delete the specified academicRecord.
     */
    public function destroy(Request $request, AcademicRecord $academicRecord)
    {
        $this->authorizeAcademicRecord($request, $academicRecord);
        $this->repository->delete($academicRecord);
    }

    /**
     * Restore a soft-deleted academicRecord.
     */
    public function restore(Request $request, string $id)
    {
        $academicRecord = $this->repository->findTrashed($id);
        $this->authorizeAcademicRecord($request, $academicRecord);
        $this->repository->restore($academicRecord);
    }

    /**
     * Permanently delete the specified academicRecord.
     */
    public function forceDelete(Request $request, AcademicRecord $academicRecord)
    {
        $this->authorizeAcademicRecord($request, $academicRecord);
        $this->repository->delete($academicRecord, true);
    }

    /**
     * Academic records are managed by the student they belong to, or by staff allowed to update that student.
     */
    private function authorizeAcademicRecord(Request $request, AcademicRecord $academicRecord): void
    {
        $this->authorize('manageStudentAcademicRecords');

        $ownStudentId = $this->getStudent($request)?->id;

        if ($ownStudentId !== null && (int) $academicRecord->student_id === (int) $ownStudentId) {
            return;
        }

        $student = Student::query()->find($academicRecord->student_id);

        abort_unless($student !== null && $request->user()->can('update', $student), 403);
    }

    /**
     * Retrieve the student profile from the request user.
     */
    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }
}
