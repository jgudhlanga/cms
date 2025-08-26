<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\FeeTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\FeeTypeRequest;
use App\Http\Resources\Shared\FeeTypeResource;
use App\Models\Shared\FeeType;
use App\Repositories\Shared\interface\IFeeTypeRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class FeeTypeController extends Controller
{
	public function __construct(protected IFeeTypeRepository $repository)
	{
	}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
		$this->authorize('viewSettings');
		$feeTypes = FeeTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/feeTypes/Index', [
			'feeTypes' => $feeTypes,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
		$this->authorize('createSettings');
	}

    /**
     * @throws AuthorizationException
     */
    public function store(FeeTypeRequest $request): void
    {
		$this->authorize('createSettings');
		$this->repository->create(FeeTypeDto::fromFeeTypeRequest($request));
	}

	public function show(FeeType $feeType)
	{
		//
	}

	public function edit(FeeType $feeType)
	{
		//
	}

    /**
     * @throws AuthorizationException
     */
    public function update(FeeTypeRequest $request, FeeType $feeType): void
    {
		$this->authorize('updateSettings');
		$this->repository->update($feeType, FeeTypeDto::fromFeeTypeRequest($request));
	}

    /**
     * @throws AuthorizationException
     */
    public function destroy(FeeType $feeType): void
    {
		$this->authorize('deleteSettings');
		$this->repository->delete($feeType);
	}

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
		$feeType = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($feeType);
	}

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(FeeType $feeType): void
    {
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($feeType, true);
	}
}
