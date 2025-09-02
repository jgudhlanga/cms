<?php

namespace App\Http\Controllers\Institution\Config;

use App\DTO\Institution\FeeStructureDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\FeeStructureFilter;
use App\Http\Requests\Institution\FeeStructureRequest;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Http\Resources\Shared\FeeTypeResource;
use App\Models\Institution\FeeStructure;
use App\Models\Shared\FeeType;
use App\Repositories\Institution\interface\IFeeStructureRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class FeeStructureController extends Controller
{
    public function __construct(protected IFeeStructureRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(FeeStructureFilter $filters): Response
    {
        $this->authorize('viewAny', FeeStructure::class);

        $query = FeeStructure::withTrashed()->with(['feeType', 'level', 'modeOfStudy'])->orderBy('level_id');
        $feeStructures = $query->get()
            ->groupBy(fn($feeStructure) => $feeStructure->feeType->name)
            ->map(fn($group) => FeeStructureResource::collection($group));
        $feeTypes = FeeType::orderBy('position')->get();
        $feeTypes = FeeTypeResource::collection($feeTypes);
        return Inertia::render('institution/feeStructures/Index', compact('feeStructures', 'feeTypes'));
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', FeeStructure::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(FeeStructureRequest $request): void
    {
        $this->authorize('create', FeeStructure::class);
        $this->repository->create(FeeStructureDto::fromFeeStructureRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(FeeStructure $feeStructure): void
    {
        $this->authorize('view', $feeStructure);
    }

    public function edit(FeeStructure $feeStructure)
    {
        //
    }

    /**
     * @throws AuthorizationException
     */
    public function update(FeeStructureRequest $request, FeeStructure $feeStructure): void
    {
        $this->authorize('update', $feeStructure);
        $this->repository->update($feeStructure, FeeStructureDto::fromFeeStructureRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(FeeStructure $feeStructure): void
    {
        $this->authorize('delete', $feeStructure);
        $this->repository->delete($feeStructure);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $feeStructure = $this->repository->findTrashed($id);
        $this->authorize('restore', $feeStructure);
        $this->repository->restore($feeStructure);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(FeeStructure $feeStructure): void
    {
        $this->authorize('forceDelete', $feeStructure);
        $this->repository->delete($feeStructure, true);
    }
}
