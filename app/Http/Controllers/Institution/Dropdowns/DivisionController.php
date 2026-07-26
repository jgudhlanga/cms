<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\DivisionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\DivisionRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\DivisionResource;
use App\Models\Institution\Division;
use App\Models\Institution\Staff;
use App\Repositories\Institution\interface\IDivisionRepository;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function __construct(protected IDivisionRepository $repository) {}

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', Division::class);
        $divisions = DivisionResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('institution/dropdowns/divisions/Index', [
            'divisions' => $divisions,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Division::class);
    }

    public function store(DivisionRequest $request)
    {
        $this->authorize('create', Division::class);
        $this->repository->create(DivisionDto::fromDivisionRequest($request));
    }

    public function show(Division $division)
    {
        //
    }

    public function edit(Division $division)
    {
        //
    }

    public function update(DivisionRequest $request, Division $division)
    {
        $this->authorize('update', $division);
        $this->repository->update($division, DivisionDto::fromDivisionRequest($request));
    }

    public function movePosition(PositionRequest $request, Division $division)
    {
        $this->authorize('update', $division);
        $this->repository->movePosition($division, $request);
    }

    public function destroy(Division $division)
    {
        $this->authorize('delete', $division);
        $this->repository->delete($division);
    }

    public function restore(string $id)
    {
        $division = $this->repository->findTrashed($id);
        $this->authorize('restore', $division);
        $this->repository->restore($division);
    }

    public function forceDelete(Division $division)
    {
        $this->authorize('forceDelete', $division);
        $this->repository->delete($division, true);
    }

    /**
     * @return list<array{id: int|string, name: string|null}>
     */
    private function staffOptions(): array
    {
        $query = Staff::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->orderByDesc('id');

        $linked = (clone $query)->whereHas('institutionDepartments')->get();
        $staff = $linked->isNotEmpty() ? $linked : $query->limit(200)->get();

        return $staff
            ->map(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
            ])
            ->values()
            ->all();
    }
}
