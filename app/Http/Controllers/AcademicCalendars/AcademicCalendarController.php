<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\DTO\AcademicCalendars\AcademicCalendarDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
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
        # "department_course" => "1"
        $departmentCourse = DepartmentCourse::where('id', request('department_course'))->firstOrFail();
        #"department_level" => "1"
        $departmentLevel = DepartmentLevel::where('id', request('department_level'))->firstOrFail();
        #"mode_of_study" => "1"
        $modeOdStudy = ModeOfStudy::where('id', request('mode_of_study'))->firstOrFail();
        return Inertia::render('institution/academicCalendars/AcademicCalendarClassesConfig', [
            'department' => new InstitutionDepartmentResource($institutionDepartment),
            'course' => new DepartmentCourseResource($departmentCourse),
            'level' => new DepartmentLevelResource($departmentLevel),
            'mode' => new ModeOfStudyResource($modeOdStudy),
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
