<?php

namespace App\Http\Controllers\Institution\Staff;

use App\DTO\Institution\CreateStaffDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\StaffRequest;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Support\Institution\DepartmentStaffRoles;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(protected IStaffRepository $repository) {}

    public function create(InstitutionDepartment $department)
    {
        $this->authorize('createDepartmentMetaData');

        return Inertia::render('institution/staff/Create', [
            'department' => InstitutionDepartmentResource::make($department),
            'allowedRoleSlugs' => array_values(DepartmentStaffRoles::allowedSlugsFor($department)),
        ]);
    }

    public function show(InstitutionDepartment $department, Staff $staff)
    {
        $this->authorize('viewDepartmentMetaData');
        $department = InstitutionDepartmentResource::make($department);
        $staff = StaffResource::make($staff);

        return Inertia::render('institution/staff/Show', compact('department', 'staff'));
    }

    public function edit(InstitutionDepartment $department, Staff $staff)
    {
        $this->authorize('viewDepartmentMetaData');

        return Inertia::render('institution/staff/Edit', [
            'department' => InstitutionDepartmentResource::make($department),
            'staff' => StaffResource::make($staff),
            'allowedRoleSlugs' => array_values(DepartmentStaffRoles::allowedSlugsFor($department)),
        ]);
    }

    /**
     * Store a newly created staff.
     */
    public function store(InstitutionDepartment $department, StaffRequest $request)
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
    public function update(InstitutionDepartment $department, StaffRequest $request, Staff $staff)
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update(
            $staff,
            CreateStaffDto::fromStaffRequest($request)
        );

        return to_route('staff.show', ['department' => $department->id, 'staff' => $staff->id]);
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
