<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelRequest;
use App\Http\Requests\HMS\UpdateHostelRequest;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoomSection;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Repositories\HMS\interface\IHostelRepository;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HostelController extends Controller
{
    public function __construct(protected IHostelRepository $repository) {}

    public function index()
    {
        return Inertia::render('hms/hostels/Index', [
            'wardens' => $this->wardenOptions(),
        ]);
    }

    public function show(Hostel $hostel)
    {
        $hostel->load([
            'warden.user:id,first_name,middle_name,last_name,email,phone_number',
            'warden.institutionDepartments.department:id,name',
            'warden.institutionDepartments.metadata:id,institution_department_id,email,phone_number,location',
        ]);
        $hostel->loadSum('rooms as occupied_beds_sum', 'current_occupancy');
        $hostel->loadCount([
            'rooms as vacant_rooms_count' => fn ($builder) => $builder->where('status', 'vacant'),
            'rooms as maintenance_rooms_count' => fn ($builder) => $builder->where('status', 'maintenance'),
        ]);
        $hostel->loadCount([
            'rooms as occupied_sections_count' => fn ($builder) => $builder
                ->join('hostel_room_allocations', 'hostel_room_allocations.hostel_room_id', '=', 'hostel_rooms.id')
                ->where('hostel_room_allocations.status', 'active'),
        ]);

        $hostel->setAttribute('sections_count', DB::table('hostel_room_sections')
            ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_sections.hostel_room_id')
            ->where('hostel_rooms.hostel_id', $hostel->id)
            ->count());
        $hostel->setAttribute('room_amenities_count', DB::table('hostel_room_amenity')
            ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_amenity.hostel_room_id')
            ->where('hostel_rooms.hostel_id', $hostel->id)
            ->count());
        $hostel->setAttribute('section_amenities_count', DB::table('amenityables')
            ->join('hostel_room_sections', function ($join) {
                $join->on('amenityables.amenityable_id', '=', 'hostel_room_sections.id')
                    ->where('amenityables.amenityable_type', HostelRoomSection::class);
            })
            ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_sections.hostel_room_id')
            ->where('hostel_rooms.hostel_id', $hostel->id)
            ->count());

        $wardens = $this->wardenOptions();

        return Inertia::render('hms/hostels/Show', [
            'hostel' => $hostel,
            'wardens' => $wardens,
            'wardenProfile' => $this->wardenProfile($hostel->warden),
        ]);
    }

    /**
     * @return list<array{id: int|string, name: string|null, gender: string|null}>
     */
    private function wardenOptions(): array
    {
        return Staff::query()
            ->select(['id', 'user_id', 'gender_id'])
            ->with([
                'user:id,first_name,middle_name,last_name',
                'gender:id,title',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
                'gender' => $staff->gender?->title,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     name: string|null,
     *     email: string|null,
     *     phone: string|null,
     *     employeeNumber: string|null,
     *     departments: list<array{
     *         id: int,
     *         name: string|null,
     *         code: string|null,
     *         email: string|null,
     *         phone: string|null,
     *         location: string|null
     *     }>
     * }|null
     */
    private function wardenProfile(?Staff $warden): ?array
    {
        if ($warden === null) {
            return null;
        }

        $user = $warden->user;

        return [
            'name' => $user?->full_name,
            'email' => $user?->email,
            'phone' => $user?->phone_number,
            'employeeNumber' => $warden->employee_number,
            'departments' => $warden->institutionDepartments
                ->map(fn (InstitutionDepartment $department): array => [
                    'id' => (int) $department->id,
                    'name' => $department->department?->name,
                    'code' => $department->department_code,
                    'email' => $department->metadata?->email,
                    'phone' => $department->metadata?->phone_number,
                    'location' => $department->metadata?->location,
                ])
                ->values()
                ->all(),
        ];
    }

    public function store(StoreHostelRequest $request): void
    {
        $this->repository->create($request->validated());
    }

    public function update(UpdateHostelRequest $request, Hostel $hostel): void
    {
        $this->repository->update($hostel, $request->validated());
    }

    public function destroy(Hostel $hostel): void
    {
        $this->repository->delete($hostel);
    }

    public function restore(string $id): void
    {
        $hostel = $this->repository->findTrashed($id);

        $this->repository->restore($hostel);
    }

    public function forceDelete(Hostel $hostel): void
    {
        $this->repository->delete($hostel, true);
    }
}
