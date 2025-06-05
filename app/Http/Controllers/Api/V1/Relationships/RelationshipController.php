<?php

namespace App\Http\Controllers\Api\V1\Relationships;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Relationships\RelationshipResource;
use App\Repositories\Relationships\interface\IRelationshipRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class RelationshipController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IRelationshipRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return RelationshipResource::collection($this->repository->allFilter(['*'], $filters));
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
