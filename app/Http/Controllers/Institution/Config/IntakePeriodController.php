<?php

namespace App\Http\Controllers\Institution\Config;

use App\DTO\Institution\IntakePeriodDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\IntakePeriodRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\IntakePeriod;
use App\Repositories\Institution\interface\IIntakePeriodRepository;
use Inertia\Inertia;

class IntakePeriodController extends Controller
{
    public function __construct(protected IIntakePeriodRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', IntakePeriod::class);
        $intakePeriods = IntakePeriodResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/intakePeriods/Index', [
            'intakePeriods' => $intakePeriods,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', IntakePeriod::class);
    }

    public function store(IntakePeriodRequest $request)
    {
        $this->authorize('create', IntakePeriod::class);
        $this->repository->create(IntakePeriodDto::fromIntakePeriodRequest($request));
    }

    public function show(IntakePeriod $intakePeriod)
    {
        //
    }

    public function edit(IntakePeriod $intakePeriod)
    {
        //
    }

    public function update(IntakePeriodRequest $request, IntakePeriod $intakePeriod)
    {
        $this->authorize('update', $intakePeriod);
        $this->repository->update($intakePeriod, IntakePeriodDto::fromIntakePeriodRequest($request));
    }

    public function destroy(IntakePeriod $intakePeriod)
    {
        $this->authorize('delete', $intakePeriod);
        $this->repository->delete($intakePeriod);
    }

    public function restore(string $id)
    {
        $intakePeriod = $this->repository->findTrashed($id);
        $this->authorize('restore', $intakePeriod);
        $this->repository->restore($intakePeriod);
    }

    public function forceDelete(IntakePeriod $intakePeriod)
    {
        $this->authorize('forceDelete', $intakePeriod);
        $this->repository->delete($intakePeriod, true);
    }
}
