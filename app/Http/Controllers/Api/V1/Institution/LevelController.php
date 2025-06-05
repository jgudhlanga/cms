<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\LevelResource;
use App\Repositories\Institution\interface\ILevelRepository;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function __construct(protected ILevelRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return LevelResource::collection($this->repository->allFilter(['*'], $filters));
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
