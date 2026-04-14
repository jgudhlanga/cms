<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\DTO\AcademicCalendars\AcademicYearOptionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\AcademicCalendars\AcademicYearOptionRequest;
use App\Http\Resources\AcademicCalendars\AcademicYearOptionResource;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Repositories\AcademicCalendars\interface\IAcademicYearOptionRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearOptionController extends Controller
{
    public function __construct(protected IAcademicYearOptionRepository $repository) {}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
        $this->authorize('viewSettings');
        $academicYearOptions = AcademicYearOptionResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('academicCalendars/academicYearOptions/Index', [
            'academicYearOptions' => $academicYearOptions,
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
    public function store(AcademicYearOptionRequest $request): void
    {
        $this->authorize('createSettings');
        $this->repository->create(AcademicYearOptionDto::fromRequest($request));
    }

    public function show(AcademicYearOption $academicYearOption): void {}

    public function edit(AcademicYearOption $academicYearOption): void {}

    /**
     * @throws AuthorizationException
     */
    public function update(AcademicYearOptionRequest $request, AcademicYearOption $academicYearOption): void
    {
        $this->authorize('updateSettings');
        $this->repository->update($academicYearOption, AcademicYearOptionDto::fromRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(AcademicYearOption $academicYearOption): void
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($academicYearOption);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $academicYearOption = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($academicYearOption);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(AcademicYearOption $academicYearOption): void
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($academicYearOption, true);
    }
}
