<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\DTO\AcademicCalendars\SemesterDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\AcademicCalendars\SemesterRequest;
use App\Http\Resources\AcademicCalendars\SemesterResource;
use App\Models\AcademicCalendars\Semester;
use App\Repositories\AcademicCalendars\interface\ISemesterRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class SemesterController extends Controller
{
    public function __construct(protected ISemesterRepository $repository) {}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
        $this->authorize('viewAny', Semester::class);
        $semesters = SemesterResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('academicCalendars/semesters/Index', [
            'semesters' => $semesters,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', Semester::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(SemesterRequest $request): void
    {
        $this->authorize('create', Semester::class);
        $this->repository->create(SemesterDto::fromRequest($request));
    }

    public function show(Semester $semester): void {}

    public function edit(Semester $semester): void {}

    /**
     * @throws AuthorizationException
     */
    public function update(SemesterRequest $request, Semester $semester): void
    {
        $this->authorize('update', $semester);
        $this->repository->update($semester, SemesterDto::fromRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Semester $semester): void
    {
        $this->authorize('delete', $semester);
        $this->repository->delete($semester);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $semester = $this->repository->findTrashed($id);
        $this->authorize('restore', $semester);
        $this->repository->restore($semester);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(Semester $semester): void
    {
        $this->authorize('forceDelete', $semester);
        $this->repository->delete($semester, true);
    }
}
