<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\DTO\AcademicYears\AcademicCalendarDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use Inertia\Inertia;

class AcademicCalendarController extends Controller
{
    public function __construct(protected IAcademicCalendarRepository $repository)
    {
    }

    public function index(AcademicCalendarFilter $filters)
    {
        $this->authorize('viewAny', AcademicCalendar::class);
        $academicCalendars = AcademicCalendarResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('academicCalendars/Index', [
            'academicCalendars' => $academicCalendars,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', AcademicCalendar::class);
    }

    public function store(AcademicCalendarRequest $request)
    {
        $this->authorize('create', AcademicCalendar::class);
        $this->repository->create(AcademicCalendarDto::fromAcademicCalendarRequest($request));
    }

    public function show(AcademicCalendar $academicCalendar)
    {
        //
    }

    public function edit(AcademicCalendar $academicCalendar)
    {
        //
    }

    public function update(AcademicCalendarRequest $request, AcademicCalendar $academicCalendar)
    {
        $this->authorize('create', $academicCalendar);
        $this->repository->update($academicCalendar, AcademicCalendarDto::fromAcademicCalendarRequest($request));
    }

    public function destroy(AcademicCalendar $academicCalendar)
    {
        $this->authorize('delete', $academicCalendar);
        $this->repository->delete($academicCalendar);
    }

    public function restore(string $id)
    {
        $academicCalendar = $this->repository->findTrashed($id);
        $this->authorize('restore', $academicCalendar);
        $this->repository->restore($academicCalendar);
    }

    public function forceDelete(AcademicCalendar $academicCalendar)
    {
        $this->authorize('forceDelete', $academicCalendar);
        $this->repository->delete($academicCalendar, true);
    }
}
