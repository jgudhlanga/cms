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
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;
use function Spatie\LaravelPdf\Support\pdf;

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
    public function create(): Response
    {
        $this->authorize('create', DocumentTemplate::class);
        return Inertia::render('institution/document-templates/Create');
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(DocumentTemplateRequest $request)
    {
        $this->authorize('create', DocumentTemplate::class);
        DB::transaction(function () use ($request) {
            $template = $this->repository->create(
                DocumentTemplateDto::fromDocumentTemplateRequest($request)
            );

            if ($request->hasFile('header_logo_1')) {
                $media = $template->addMedia($request->file('header_logo_1'))
                    ->toMediaCollection('logo-1');
                $template->update(['header_logo_1' => $media->id]);
            }

            if ($request->hasFile('header_logo_2')) {
                $media = $template->addMedia($request->file('header_logo_2'))
                    ->toMediaCollection('logo-2');
                $template->update(['header_logo_2' => $media->id]);
            }
        });
        return to_route('document-templates.index');
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

    public function preview(DocumentTemplate $documentTemplate)
    {
        $this->authorize('view', $documentTemplate);
        $fileName = 'offer-letter-' . time() . '.pdf';
        return pdf()->view('students.offer-letter', compact('documentTemplate'))->name($fileName);
    }
}
