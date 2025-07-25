<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Repositories\Institution\IntakePeriodRepository;
use Illuminate\Http\Request;

class IntakePeriodController extends Controller
{
    public function __construct(protected IntakePeriodRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return IntakePeriodResource::collection($this->repository->allFilter(['*'], $filters));
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
