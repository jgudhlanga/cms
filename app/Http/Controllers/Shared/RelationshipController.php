<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\RelationshipDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\RelationshipRequest;
use App\Http\Resources\Shared\RelationshipResource;
use App\Models\Shared\Relationship;
use App\Repositories\Shared\interface\IRelationshipRepository;
use Inertia\Inertia;

class RelationshipController extends Controller
{
    public function __construct(protected IRelationshipRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', Relationship::class);
        $relationships = RelationshipResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/relationships/Index', [
            'relationships' => $relationships,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Relationship::class);
    }

    public function store(RelationshipRequest $request)
    {
        $this->authorize('create', Relationship::class);
        $this->repository->create(RelationshipDto::fromRelationshipRequest($request));
    }

    public function show(Relationship $relationship)
    {
        //
    }

    public function edit(Relationship $relationship)
    {
        //
    }

    public function update(RelationshipRequest $request, Relationship $relationship)
    {
        $this->authorize('update', $relationship);
        $this->repository->update($relationship, RelationshipDto::fromRelationshipRequest($request));
    }

    public function destroy(Relationship $relationship)
    {
        $this->authorize('delete', $relationship);
        $this->repository->delete($relationship);
    }

    public function restore(string $id)
    {
        $relationship = $this->repository->findTrashed($id);
        $this->authorize('restore', $relationship);
        $this->repository->restore($relationship);
    }

    public function forceDelete(Relationship $relationship)
    {
        $this->authorize('forceDelete', $relationship);
        $this->repository->delete($relationship, true);
    }
}
