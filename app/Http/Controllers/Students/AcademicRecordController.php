<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\AcademicRecordDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\AcademicRecordRequest;
use App\Models\Students\AcademicRecord;
use App\Repositories\Students\interface\IAcademicRecordRepository;
use Illuminate\Http\Request;

class AcademicRecordController extends Controller
{
    public function __construct(protected IAcademicRecordRepository $repository)
    {
    }

    /**
     * Store a newly created academicRecord.
     */
    public function store(AcademicRecordRequest $request)
    {
        $this->repository->create(
            AcademicRecordDto::fromAcademicRecordRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Update the specified academicRecord.
     */
    public function update(AcademicRecordRequest $request, AcademicRecord $academicRecord)
    {
        $this->repository->update(
            $academicRecord,
            AcademicRecordDto::fromAcademicRecordRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Soft delete the specified academicRecord.
     */
    public function destroy(AcademicRecord $academicRecord)
    {
        $this->repository->delete($academicRecord);
    }

    /**
     * Restore a soft-deleted academicRecord.
     */
    public function restore(string $id)
    {
        $academicRecord = $this->repository->findTrashed($id);
        $this->repository->restore($academicRecord);
    }

    /**
     * Permanently delete the specified academicRecord.
     */
    public function forceDelete(AcademicRecord $academicRecord)
    {
        $this->repository->delete($academicRecord, true);
    }

    /**
     * Retrieve the student profile from the request user.
     */
    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }
}
