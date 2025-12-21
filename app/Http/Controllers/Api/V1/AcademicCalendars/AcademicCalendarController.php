<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Acl\RoleResource;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class AcademicCalendarController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IAcademicCalendarRepository $repository)
    {

    }

    public function index(AcademicCalendarFilter $filters)
    {
        return AcademicCalendarResource::collection($this->repository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
