<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Shared\DocumentTypeResource;
use App\Repositories\Shared\interface\IDocumentTypeRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentTypeController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IDocumentTypeRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return DocumentTypeResource::collection($this->repository->allFilter(['*'], $filters));
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
