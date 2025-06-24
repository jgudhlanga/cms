<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\TitleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\TitleRequest;
use App\Http\Resources\Shared\TitleResource;
use App\Models\Shared\Title;
use App\Repositories\Shared\interface\ITitleRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SponsorController extends Controller
{
    public function __construct(protected ITitleRepository $repository)
    {
    }

    public function store(TitleRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(TitleDto::fromTitleRequest($request));
    }


    public function update(TitleRequest $request, Title $title)
    {
        $this->authorize('updateSettings');
        $this->repository->update($title, TitleDto::fromTitleRequest($request));
    }

    public function destroy(Title $title)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($title);
    }

    public function restore(string $id)
    {
        $title = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($title);
    }

    public function forceDelete(Title $title)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($title, true);
    }
}
