<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\SyncHostelRoomSectionAmenitiesRequest;
use App\Http\Requests\HMS\StoreHostelRoomRequest;
use App\Http\Requests\HMS\UpdateHostelRoomRequest;
use App\Models\HMS\HostelAmenity;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomSection;
use App\Repositories\HMS\interface\IHostelRoomRepository;
use App\Services\HMS\HostelRoomSectionService;
use Inertia\Inertia;

class HostelRoomController extends Controller
{
    public function __construct(
        protected IHostelRoomRepository $repository,
        protected HostelRoomSectionService $sectionService,
    ) {}

    public function show(HostelRoom $hostelRoom)
    {
        $this->authorize('view', $hostelRoom);
        $this->sectionService->ensureSectionsForRoom($hostelRoom);

        $hostelRoom->refresh()->load([
            'hostel:id,name',
            'amenities:id,name,slug',
            'sections.amenities:id,name,slug',
            'allocations' => fn ($query) => $query->active()->with([
                'student.user:id,first_name,middle_name,last_name',
                'student.latestEnrolment.departmentCourse.course:id,name',
            ]),
        ]);

        $amenities = HostelAmenity::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (HostelAmenity $amenity): array => [
                'id' => (int) $amenity->id,
                'name' => (string) $amenity->name,
                'slug' => (string) $amenity->slug,
            ])
            ->values();

        return Inertia::render('hms/hostel-rooms/Show', [
            'room' => [
                'id' => $hostelRoom->id,
                'hostel_id' => $hostelRoom->hostel_id,
                'name' => $hostelRoom->name,
                'room_type' => $hostelRoom->room_type,
                'capacity' => $hostelRoom->capacity,
                'status' => $hostelRoom->status,
                'max_occupancy' => $hostelRoom->max_occupancy,
                'floor_number' => $hostelRoom->floor_number,
                'description' => $hostelRoom->description,
                'hostel' => [
                    'id' => $hostelRoom->hostel?->id,
                    'name' => $hostelRoom->hostel?->name,
                ],
                'amenities' => $hostelRoom->amenities
                    ->map(fn (HostelAmenity $amenity): array => [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'slug' => $amenity->slug,
                    ])
                    ->values(),
                'sections' => $hostelRoom->sections
                    ->map(fn (HostelRoomSection $section): array => [
                        'id' => $section->id,
                        'name' => $section->name,
                        'amenities' => $section->amenities
                            ->map(fn (HostelAmenity $amenity): array => [
                                'id' => $amenity->id,
                                'name' => $amenity->name,
                                'slug' => $amenity->slug,
                            ])
                            ->values(),
                    ])
                    ->values(),
                'allocations' => $hostelRoom->allocations
                    ->map(fn ($allocation): array => [
                        'id' => $allocation->id,
                        'hostel_room_section_id' => $allocation->hostel_room_section_id,
                        'student' => $allocation->student ? [
                            'id' => $allocation->student->id,
                            'student_number' => $allocation->student->student_number,
                            'user' => [
                                'full_name' => $allocation->student->user?->full_name,
                            ],
                            'course' => $allocation->student->latestEnrolment?->departmentCourse?->course?->name,
                        ] : null,
                    ])
                    ->values(),
            ],
            'amenities' => $amenities,
        ]);
    }

    public function store(StoreHostelRoomRequest $request): void
    {
        $this->authorize('create', HostelRoom::class);
        $this->repository->create($request->validated());
    }

    public function update(UpdateHostelRoomRequest $request, HostelRoom $hostelRoom): void
    {
        $this->authorize('update', $hostelRoom);
        $this->repository->update($hostelRoom, $request->validated());
    }

    public function destroy(HostelRoom $hostelRoom): void
    {
        $this->authorize('delete', $hostelRoom);
        $this->repository->delete($hostelRoom);
    }

    public function restore(string $id): void
    {
        $room = $this->repository->findTrashed($id);
        $this->authorize('restore', $room);
        $this->repository->restore($room);
    }

    public function forceDelete(HostelRoom $hostelRoom): void
    {
        $this->authorize('forceDelete', $hostelRoom);
        $this->repository->delete($hostelRoom, true);
    }

    public function syncSectionAmenities(
        SyncHostelRoomSectionAmenitiesRequest $request,
        HostelRoom $hostelRoom,
        HostelRoomSection $hostelRoomSection,
    ): void {
        $this->authorize('update', $hostelRoom);
        abort_unless((int) $hostelRoomSection->hostel_room_id === (int) $hostelRoom->id, 404);

        $hostelRoomSection->amenities()->sync($request->validated('amenity_ids', []));
    }
}
