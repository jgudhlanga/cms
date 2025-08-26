<?php

namespace App\Http\Controllers\Institution\DocumentTemplates;

use App\DTO\DocumentTemplates\DocumentTemplateDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\DocumentTemplates\DocumentTemplateRequest;
use App\Http\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\Acl\Role;
use App\Models\Institution\DocumentTemplate;
use App\Repositories\Institution\interface\IDocumentTemplateRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTemplateController extends Controller
{
    public function __construct(protected IDocumentTemplateRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
        $this->authorize('viewAny', DocumentTemplate::class);
        $documentTemplates = DocumentTemplateResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/document-templates/Index', [
            'documentTemplates' => $documentTemplates,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', DocumentTemplate::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(DocumentTemplateRequest $request): void
    {
        $this->authorize('create', DocumentTemplate::class);
        $this->repository->create(DocumentTemplateDto::fromDocumentTemplateRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('view', $documentTemplate);
        //
    }

    public function edit(Role $role)
    {
        //
    }

    /**
     * @throws AuthorizationException
     */
    public function update(DocumentTemplateRequest $request, DocumentTemplate $documentTemplate): void
    {
        $this->authorize('update', $documentTemplate);
        $this->repository->update($documentTemplate, DocumentTemplateDto::fromDocumentTemplateRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('delete', $documentTemplate);
        $this->repository->delete($documentTemplate);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $documentTemplate = $this->repository->findTrashed($id);
        $this->authorize('restore', $documentTemplate);
        $this->repository->restore($documentTemplate);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('forceDelete', $documentTemplate);
        $this->repository->delete($documentTemplate, true);
    }
}
