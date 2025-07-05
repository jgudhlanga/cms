<?php

namespace App\Http\Controllers\Institution\Staff;


use App\DTO\Institution\CreateStaffDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CreateStaffRequest;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Repositories\Institution\interface\IStaffRepository;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(protected IStaffRepository $repository)
    {
    }

    public function create(InstitutionDepartment $department)
    {
        $this->authorize('createDepartmentMetaData');
        $department = InstitutionDepartmentResource::make($department);
        return Inertia::render('institution/staff/Create', compact('department'));
    }

    public function show(InstitutionDepartment $department, Staff $staff)
    {
        $this->authorize('viewDepartmentMetaData');
        $department = InstitutionDepartmentResource::make($department);
        $staff = StaffResource::make($staff);
        return Inertia::render('institution/staff/Show', compact('department', 'staff'));
    }

    /**
     * Store a newly created staff.
     */
    public function store(InstitutionDepartment $department, CreateStaffRequest $request)
    {
        $this->authorize('createDepartmentMetaData');
        $staff = $this->repository->create(
            CreateStaffDto::fromStaffRequest($request)
        );
        return to_route('staff.show', ['department' => $department->id, 'staff' => $staff->id]);
    }

    /**
     * Update the specified staff.
     */
    public function update(CreateStaffRequest $request, Staff $staff)
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update(
            $staff,
            CreateStaffDto::fromStaffRequest($request, $this->getUser())
        );
    }

    /**
     * Soft delete the specified staff.
     */
    public function destroy(Staff $staff)
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($staff);
    }

    /**
     * Restore a soft-deleted staff.
     */
    public function restore(string $id)
    {
        $this->authorize('restoreDepartmentMetaData');
        $staff = $this->repository->findTrashed($id);
        $this->repository->restore($staff);
    }

    /**
     * Permanently delete the specified staff.
     */
    public function forceDelete(Staff $staff)
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($staff, true);
    }

    /**
     * Retrieve the user from the request user.
     */
    private function getUser()
    {
        return request()->user();
    }
}
