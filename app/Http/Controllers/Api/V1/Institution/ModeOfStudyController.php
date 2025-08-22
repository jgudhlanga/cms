<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeOfStudyController extends Controller
{
    public function __construct(protected IModeOfStudyRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return ModeOfStudyResource::collection($this->repository->allFilter(['*'], $filters));
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
