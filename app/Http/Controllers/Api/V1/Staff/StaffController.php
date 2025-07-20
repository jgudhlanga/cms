<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Institution\StaffFilter;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\Staff;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class StaffController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IStaffRepository $repository)
    {

    }

    public function index(StaffFilter $filters)
    {
        return StaffResource::collection($this->repository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function store(Request $request)
    {
    }

    public function show(Staff $staff)
    {
        return StaffResource::make($staff);
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
