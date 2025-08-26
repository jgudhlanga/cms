<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\DocumentTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\DocumentTypeRequest;
use App\Http\Resources\Shared\DocumentTypeResource;
use App\Models\Shared\DocumentType;
use App\Repositories\Shared\interface\IDocumentTypeRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeController extends Controller
{
	public function __construct(protected IDocumentTypeRepository $repository)
	{
	}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
		$this->authorize('viewSettings');
		$documentTypes = DocumentTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/documentTypes/Index', [
			'documentTypes' => $documentTypes,
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
    public function store(DocumentTypeRequest $request): void
    {
		$this->authorize('createSettings');
		$this->repository->create(DocumentTypeDto::fromDocumentTypeRequest($request));
	}

	public function show(DocumentType $documentType)
	{
		//
	}

	public function edit(DocumentType $documentType)
	{
		//
	}

    /**
     * @throws AuthorizationException
     */
    public function update(DocumentTypeRequest $request, DocumentType $documentType): void
    {
		$this->authorize('updateSettings');
		$this->repository->update($documentType, DocumentTypeDto::fromDocumentTypeRequest($request));
	}

    /**
     * @throws AuthorizationException
     */
    public function destroy(DocumentType $documentType): void
    {
		$this->authorize('deleteSettings');
		$this->repository->delete($documentType);
	}

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
		$documentType = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($documentType);
	}

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DocumentType $documentType): void
    {
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($documentType, true);
	}
}
