<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\DTO\AcademicCalendars\AcademicCalendarDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCalendarController extends Controller
{
    public function __construct(protected IAcademicCalendarRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(AcademicCalendarFilter $filters): Response
    {
        $this->authorize('viewAny', AcademicCalendar::class);
        $academicCalendars = AcademicCalendarResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('institution/academicCalendars/Index', [
            'academicCalendars' => $academicCalendars,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', AcademicCalendar::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(AcademicCalendarRequest $request): void
    {
        $this->authorize('create', AcademicCalendar::class);
        $this->repository->create(AcademicCalendarDto::fromAcademicCalendarRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(AcademicCalendar $academicCalendar)
    {
        /* $this->authorize('view', $academicCalendar);
         return Inertia::render('academicCalendars/Show', [
             'academicCalendar' => new AcademicCalendarResource($academicCalendar)
         ]);*/
    }

    public function edit(AcademicCalendar $academicCalendar)
    {

    }

    /**
     * @throws AuthorizationException
     */
    public function configDepartmentCourseClasses(InstitutionDepartment $institutionDepartment, AcademicCalendar $academicCalendar)
    {
        $this->authorize('update', $academicCalendar);
        return Inertia::render('institution/academicCalendars/AcademicCalendarClassesConfig', [
            'department' => new InstitutionDepartmentResource($institutionDepartment),
            'academicCalendar' => new AcademicCalendarResource($academicCalendar),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(AcademicCalendarRequest $request, AcademicCalendar $academicCalendar): void
    {
        $this->authorize('update', $academicCalendar);
        $this->repository->update($academicCalendar, AcademicCalendarDto::fromAcademicCalendarRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(AcademicCalendar $academicCalendar): void
    {
        $this->authorize('delete', $academicCalendar);
        $this->repository->delete($academicCalendar);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $academicCalendar = $this->repository->findTrashed($id);
        $this->authorize('restore', $academicCalendar);
        $this->repository->restore($academicCalendar);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(AcademicCalendar $academicCalendar): void
    {
        $this->authorize('forceDelete', $academicCalendar);
        $this->repository->delete($academicCalendar, true);
    }
}
