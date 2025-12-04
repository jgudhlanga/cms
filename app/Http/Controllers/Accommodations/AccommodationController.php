<?php

namespace App\Http\Controllers\Accommodations;

use App\DTO\AcademicYears\AcademicCalendarDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccommodationController extends Controller
{
    public function __construct(protected IAcademicCalendarRepository $repository)
    {
    }

    public function index(AcademicCalendarFilter $filters)
    {
       // $this->authorize('viewAny', AcademicCalendar::class);
        return Inertia::render('accommodations/Index');
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
