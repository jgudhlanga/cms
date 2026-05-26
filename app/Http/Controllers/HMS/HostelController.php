<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelRequest;
use App\Http\Requests\HMS\UpdateHostelRequest;
use App\Models\HMS\Hostel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Repositories\HMS\interface\IHostelRepository;
use Inertia\Inertia;

class HostelController extends Controller
{
    public function __construct(protected IHostelRepository $repository) {}

    public function index()
    {
        $wardens = Staff::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
            ])
            ->values();

        return Inertia::render('hms/hostels/Index', [
            'wardens' => $wardens,
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

        $wardens = Staff::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
            ])
            ->values();

        return Inertia::render('hms/hostels/Show', [
            'hostel' => $hostel,
            'wardens' => $wardens,
            'wardenProfile' => $this->wardenProfile($hostel->warden),
        ]);
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
