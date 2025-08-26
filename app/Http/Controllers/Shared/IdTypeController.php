<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\IdTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\IdTypeRequest;
use App\Http\Resources\Shared\IdTypeResource;
use App\Models\Shared\IdType;
use App\Repositories\Shared\interface\IIdTypeRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class IdTypeController extends Controller
{
	public function __construct(protected IIdTypeRepository $repository)
	{
	}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
		$this->authorize('viewSettings');
		$idTypes = IdTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/idTypes/Index', [
			'idTypes' => $idTypes,
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
    public function store(IdTypeRequest $request): void
    {
		$this->authorize('createSettings');
		$this->repository->create(IdTypeDto::fromIdTypeRequest($request));
	}

	public function show(IdType $idType)
	{
		//
	}

	public function edit(IdType $idType)
	{
		//
	}

    /**
     * @throws AuthorizationException
     */
    public function update(IdTypeRequest $request, IdType $idType): void
    {
		$this->authorize('updateSettings');
		$this->repository->update($idType, IdTypeDto::fromIdTypeRequest($request));
	}

    /**
     * @throws AuthorizationException
     */
    public function destroy(IdType $idType): void
    {
		$this->authorize('deleteSettings');
		$this->repository->delete($idType);
	}

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
		$idType = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($idType);
	}

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(IdType $idType): void
    {
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($idType, true);
	}
}
