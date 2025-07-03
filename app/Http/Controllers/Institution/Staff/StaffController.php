<?php

namespace App\Http\Controllers\Institution\Staff;


use App\DTO\Institution\CreateStaffDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CreateStaffRequest;
use App\Models\Institution\Staff;
use App\Repositories\Institution\interface\IStaffRepository;

class StaffController extends Controller
{
    public function __construct(protected IStaffRepository $repository)
    {
    }

    /**
     * Store a newly created staff.
     */
    public function store(CreateStaffRequest $request)
    {
        $this->repository->create(
            CreateStaffDto::fromStaffRequest($request, $this->getUser())
        );
    }

    /**
     * Update the specified staff.
     */
    public function update(CreateStaffRequest $request, Staff $staff)
    {
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
        $this->repository->delete($staff);
    }

    /**
     * Restore a soft-deleted staff.
     */
    public function restore(string $id)
    {
        $staff = $this->repository->findTrashed($id);
        $this->repository->restore($staff);
    }

    /**
     * Permanently delete the specified staff.
     */
    public function forceDelete(Staff $staff)
    {
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
